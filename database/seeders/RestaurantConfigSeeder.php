<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class RestaurantConfigSeeder extends Seeder
{
    /**
     * Seed restaurant-specific system configurations.
     * Safe to run multiple times (updateOrCreate).
     */
    public function run(): void
    {
        $openingHoursJson = json_encode([
            'saturday'  => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'sunday'    => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'monday'    => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'tuesday'   => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'wednesday' => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'thursday'  => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'],
            'friday'    => ['is_open' => true, 'from' => '12:00', 'to' => '23:00'],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $rejectionReasonsJson = json_encode([
            '\u062e\u0627\u0631\u062c \u0646\u0637\u0627\u0642 \u0627\u0644\u062a\u0648\u0635\u064a\u0644',
            '\u0627\u0644\u0645\u0646\u062a\u062c \u063a\u064a\u0631 \u0645\u062a\u0648\u0641\u0631',
            '\u0636\u063a\u0637 \u0637\u0644\u0628\u0627\u062a \u062d\u0627\u0644\u064a\u0627\u064b',
            '\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0632\u0628\u0648\u0646 \u063a\u064a\u0631 \u0635\u062d\u064a\u062d\u0629',
            '\u0633\u0628\u0628 \u0622\u062e\u0631',
        ], JSON_UNESCAPED_UNICODE);

        $configs = [
            ['key' => 'restaurant_name',               'value' => '\u0627\u0633\u0645 \u0627\u0644\u0645\u0637\u0639\u0645'],
            ['key' => 'restaurant_logo',               'value' => null],
            ['key' => 'restaurant_phone',              'value' => null],
            ['key' => 'restaurant_whatsapp',           'value' => null],
            ['key' => 'is_accepting_orders',           'value' => 'true'],
            ['key' => 'customer_cancel_before_minutes','value' => '60'],
            ['key' => 'order_closed_message',          'value' => '\u0627\u0644\u0645\u0637\u0639\u0645 \u064a\u0633\u062a\u0642\u0628\u0644 \u0627\u0644\u062a\u0635\u0641\u062d \u062d\u0627\u0644\u064a\u0627\u064b\u060c \u0644\u0643\u0646 \u0627\u0644\u0637\u0644\u0628\u0627\u062a \u0645\u063a\u0644\u0642\u0629 \u062e\u0627\u0631\u062c \u0623\u0648\u0642\u0627\u062a \u0627\u0644\u0639\u0645\u0644.'],
            ['key' => 'delivery_note',                 'value' => '\u0623\u0633\u0639\u0627\u0631 \u0627\u0644\u062a\u0648\u0635\u064a\u0644 \u062a\u0642\u0631\u064a\u0628\u064a\u0629 \u0648\u064a\u062d\u062f\u062f \u0627\u0644\u0645\u0648\u0638\u0641 \u0627\u0644\u0633\u0639\u0631 \u0627\u0644\u0646\u0647\u0627\u0626\u064a \u0628\u0639\u062f \u0645\u0631\u0627\u062c\u0639\u0629 \u0627\u0644\u0637\u0644\u0628.'],
            ['key' => 'opening_hours',   'value' => $openingHoursJson],
            ['key' => 'rejection_reasons','value' => $rejectionReasonsJson],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value'], 'group' => 'restaurant']
            );
        }
    }
}
