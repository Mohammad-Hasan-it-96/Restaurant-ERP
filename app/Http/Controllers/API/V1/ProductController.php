<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Columns the ProductResource + its accessors actually read. Limiting the
     * SELECT to these keeps the payload identical while skipping unused columns
     * (user_id, quantity, timestamps). `name`/`name_ar`/`name_en` feed the
     * display_name accessor; `price`/`discount_price` feed effective_price.
     */
    private const LIST_COLUMNS = [
        'id', 'category_id', 'name', 'name_ar', 'name_en', 'details',
        'description_ar', 'description_en', 'price', 'discount_price',
        'price_per_kg', 'is_weight_based', 'image', 'slug',
        'is_available', 'is_featured', 'is_active', 'sort_order',
    ];

    /** Max length of the client `search` term — caps LIKE cost and cache-key size. */
    private const MAX_SEARCH_LENGTH = 50;

    /**
     * GET /api/v1/products
     *
     * Supports: category_id, featured=1, search, per_page
     *
     * Responses are cached for 5 minutes, keyed per distinct query (and per
     * locale, since display_name/category name are locale-dependent). The cache
     * is flushed on any product create/update/delete via Product model events.
     */
    public function index(Request $request): JsonResponse
    {
        // Normalize + whitelist inputs ONCE so (a) the cache key can't be exploded
        // by arbitrary junk params, and (b) a giant search term can't drive an
        // unbounded LIKE scan. The cache key is built from these normalized values.
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $featured = $request->boolean('featured');

        $search = trim((string) $request->input('search', ''));
        if (mb_strlen($search) > self::MAX_SEARCH_LENGTH) {
            $search = mb_substr($search, 0, self::MAX_SEARCH_LENGTH);
        }

        $perPage = (int) $request->input('per_page', 0);
        if ($perPage > 0) {
            $perPage = min($perPage, (int) config('api.max_per_page'));
        }

        // Key from the normalized whitelist only (+ locale + list-cache version,
        // which a write bumps to invalidate all entries on non-taggable stores).
        $cacheKey = 'products.'.Product::listCacheVersion().'.'.app()->getLocale().'.'
            .md5($categoryId.'|'.($featured ? 1 : 0).'|'.$search.'|'.$perPage);

        $data = Product::rememberList($cacheKey, config('api.product_list_ttl'), function () use ($categoryId, $featured, $search, $perPage, $request) {
            $query = Product::query()
                ->select(self::LIST_COLUMNS)
                ->with(['category:id,name_ar,name_en', 'weights', 'optionValues.option'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id');

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($featured) {
                $query->where('is_featured', true);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            }

            if ($perPage > 0) {
                $paginated = $query->paginate($perPage);

                return [
                    'items' => ProductResource::collection($paginated->items())->resolve($request),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                ];
            }

            // Default (no per_page): the full active menu, but bounded by a generous
            // safety cap so the payload can never be unbounded. Fetch one extra row
            // to detect — and log, never silently — an actual truncation.
            $max = (int) config('api.product_list_max');
            $products = $query->limit($max + 1)->get();

            if ($products->count() > $max) {
                logService()->warning('products.list.truncated', ['max' => $max]);
                $products = $products->take($max);
            }

            return ProductResource::collection($products)->resolve($request);
        });

        return $this->success($data);
    }
}
