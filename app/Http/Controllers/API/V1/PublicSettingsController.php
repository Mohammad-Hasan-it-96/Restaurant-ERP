<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\SystemConfigService;
use App\Support\Feature;
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
        $logoPath = $this->config->getFirstText(['restaurant_logo', 'site_logo'], '');

        $payload = [
            // Prefer restaurant keys; fallback to general when empty.
            'restaurant_name' => $this->config->getFirstText(['restaurant_name_ar', 'restaurant_name', 'site_name'], config('app.name')),
            'restaurant_name_en' => $this->config->getFirstText(['restaurant_name_en'], ''),
            'restaurant_logo' => $logoPath !== '' ? asset('storage/'.ltrim($logoPath, '/')) : null,
            'restaurant_phone' => $this->config->getFirstText(['restaurant_phone', 'support_phone'], ''),
            'restaurant_whatsapp' => $this->config->getFirstText(['restaurant_whatsapp'], ''),
            'opening_hours' => $this->config->getOpeningHours(),
            'is_accepting_orders' => $this->config->isAcceptingOrders(),
            'is_open_now' => $this->config->isOpenAt(),
            'delivery_note' => $this->config->getFirstText(['delivery_note'], ''),
            'customer_cancel_before_minutes' => (int) $this->config->getNumber('customer_cancel_before_minutes', 0),
            // Currency (code/symbol/position/decimals) so the SPA renders prices consistently.
            'currency' => $this->config->currency(),
            // Brand theme tokens (config-driven) applied to the SPA :root at boot.
            'theme' => config('theme'),
            // Whitelisted feature flags only — admin-only flags are never exposed.
            'features' => Feature::clientSafe(),
        ];

        return $this->success($payload);
    }
}
