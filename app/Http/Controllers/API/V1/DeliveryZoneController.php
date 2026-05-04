<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;

class DeliveryZoneController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/delivery-zones
     */
    public function index(): JsonResponse
    {
        $zones = DeliveryZone::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->success(DeliveryZoneResource::collection($zones));
    }
}

