<?php

namespace Tests\Feature;

use App\Services\SystemConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyConfigTest extends TestCase
{
    use RefreshDatabase;

    private function config(): SystemConfigService
    {
        return app(SystemConfigService::class);
    }

    public function test_format_money_suffix_with_no_decimals(): void
    {
        $config = $this->config();
        $config->set('currency_symbol', 'ل.س', 'general');
        $config->set('currency_position', 'suffix', 'general');
        $config->set('currency_decimals', '0', 'general');

        $this->assertSame('1,500 ل.س', $config->formatMoney(1500));
    }

    public function test_format_money_prefix_with_decimals(): void
    {
        $config = $this->config();
        $config->set('currency_symbol', '$', 'general');
        $config->set('currency_position', 'prefix', 'general');
        $config->set('currency_decimals', '2', 'general');

        $this->assertSame('$1,234.50', $config->formatMoney(1234.5));
    }

    public function test_money_helper_uses_configured_currency(): void
    {
        $config = $this->config();
        $config->set('currency_symbol', '€', 'general');
        $config->set('currency_position', 'suffix', 'general');
        $config->set('currency_decimals', '0', 'general');

        $this->assertSame('2,000 €', money(2000));
        $this->assertSame('€', currency_symbol());
    }

    public function test_currency_falls_back_to_safe_defaults(): void
    {
        // Nothing seeded → USD/$ suffix/0 defaults.
        $currency = $this->config()->currency();

        $this->assertSame('USD', $currency['code']);
        $this->assertSame('$', $currency['symbol']);
        $this->assertSame('suffix', $currency['position']);
        $this->assertSame(0, $currency['decimals']);
    }

    public function test_public_settings_payload_exposes_currency_and_theme(): void
    {
        $config = $this->config();
        $config->set('currency_symbol', '$', 'general');

        $response = $this->getJson('/api/v1/settings/public');

        $response->assertOk()
            ->assertJsonPath('data.currency.symbol', '$')
            ->assertJsonPath('data.theme.primary', config('theme.primary'));
    }

    public function test_theme_config_resolves_brand_tokens(): void
    {
        $this->assertNotEmpty(config('theme.primary'));
        $this->assertNotEmpty(config('theme.primary_dark'));
        $this->assertNotEmpty(config('theme.primary_light'));
    }
}
