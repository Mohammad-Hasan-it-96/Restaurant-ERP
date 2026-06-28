<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/categories
     */
    public function index(): JsonResponse
    {
        // Cache the active-category models (locale-independent); the resource
        // renders the per-request locale. Flushed by Category model events.
        $categories = Cache::remember(Category::PUBLIC_CACHE_KEY, 3600, fn () => Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get());

        return $this->success(CategoryResource::collection($categories));
    }
}
