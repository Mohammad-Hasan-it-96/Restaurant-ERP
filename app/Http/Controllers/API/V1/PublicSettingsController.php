<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\SystemConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PublicSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(protected SystemConfigService $config) {}

    /**
     * GET /api/v1/settings/public
     */
    public function __invoke(): JsonResponse
    {
        $logoPath = $this->config->getFirstText(['restaurant_logo', 'site_logo'], '');

        $payload = [
            // Prefer restaurant keys; fallback to dashboard general/support keys when empty.
            'restaurant_name'      => $this->config->getFirstText(['restaurant_name', 'site_name'], config('app.name')),
            'restaurant_logo'      => $logoPath !== '' ? asset('storage/' . ltrim($logoPath, '/')) : null,
            'restaurant_phone'     => $this->config->getFirstText(['restaurant_phone', 'support_phone'], ''),
            'restaurant_whatsapp'  => $this->config->getFirstText(['restaurant_whatsapp'], ''),
            'opening_hours'        => $this->config->getOpeningHours(),
            'is_accepting_orders'  => $this->config->isAcceptingOrders(),
            'is_open_now'          => $this->config->isOpenAt(),
            'delivery_note'        => $this->config->getFirstText(['delivery_note'], ''),
        ];

        Log::debug('settings.public.response', $payload);

        return $this->success($payload);
    }
}
