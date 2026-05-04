<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\SystemConfigService;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(protected SystemConfigService $config) {}

    /**
     * GET /api/v1/settings/public
     */
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'restaurant_name'    => $this->config->get('restaurant_name', config('app.name')),
            'restaurant_logo'    => $this->config->get('restaurant_logo')
                                     ? asset('storage/' . $this->config->get('restaurant_logo'))
                                     : null,
            'restaurant_phone'   => $this->config->get('restaurant_phone'),
            'restaurant_whatsapp'=> $this->config->get('restaurant_whatsapp'),
            'opening_hours'      => $this->config->getOpeningHours(),
            'is_open_now'        => $this->config->isOpenAt(),
            'delivery_note'      => $this->config->get('delivery_note'),
        ]);
    }
}

