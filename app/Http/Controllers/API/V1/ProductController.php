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
     * GET /api/v1/products
     *
     * Supports: category_id, featured=1, search, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')
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
                $q->where('name_ar', 'like', '%' . $search . '%')
                  ->orWhere('name_en', 'like', '%' . $search . '%')
                  ->orWhere('name',    'like', '%' . $search . '%');
            });
        }

        $perPage = (int) $request->input('per_page', 0);

        if ($perPage > 0) {
            $paginated = $query->paginate(min($perPage, 100));
            return $this->success([
                'items'        => ProductResource::collection($paginated->items()),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ]);
        }

        return $this->success(ProductResource::collection($query->get()));
    }
}

