<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/delivery-zones
     */
    public function index(): JsonResponse
    {
        // Cache the active zones (locale-independent). Flushed by DeliveryZone model events.
        $zones = Cache::remember(DeliveryZone::PUBLIC_CACHE_KEY, config('api.delivery_zones_list_ttl'), fn () => DeliveryZone::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get());

        return $this->success(DeliveryZoneResource::collection($zones));
    }
}
