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
        // Key per query + locale, namespaced by the list-cache version so a
        // write (which bumps the version on non-taggable stores) invalidates all.
        $cacheKey = 'products.'
            .Product::listCacheVersion().'.'
            .app()->getLocale().'.'
            .md5(json_encode($request->all()));

        $data = Product::rememberList($cacheKey, config('api.product_list_ttl'), function () use ($request) {
            $query = Product::query()
                ->select(self::LIST_COLUMNS)
                ->with(['category:id,name_ar,name_en', 'weights', 'optionValues.option'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id');

            if ($categoryId = $request->input('category_id')) {
                $query->where('category_id', $categoryId);
            }

            if ($request->boolean('featured')) {
                $query->where('is_featured', true);
            }

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            }

            $perPage = (int) $request->input('per_page', 0);

            if ($perPage > 0) {
                $paginated = $query->paginate(min($perPage, config('api.max_per_page')));

                return [
                    'items' => ProductResource::collection($paginated->items())->resolve($request),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                ];
            }

            $products = $query->get();

            return ProductResource::collection($products)->resolve($request);
        });

        return $this->success($data);
    }
}
