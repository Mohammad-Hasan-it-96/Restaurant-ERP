<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    /**
     * Seed all system configurations for the restaurant ERP.
     * Safe to re-run (updateOrCreate).
     */
    public function run(): void
    {
        // ── Opening hours (all days 00:00–23:59 by default) ──────────────
        $openingHours = json_encode([
            'saturday'  => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'sunday'    => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'monday'    => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'tuesday'   => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'wednesday' => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'thursday'  => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
            'friday'    => ['is_open' => true, 'from' => '08:00', 'to' => '00:00'],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // ── Rejection reasons (Arabic) ────────────────────────────────────
        $rejectionReasons = json_encode([
            'خارج نطاق التوصيل',
            'المنتج غير متوفر',
            'ضغط طلبات حالياً',
            'بيانات الزبون غير صحيحة',
            'سبب آخر',
        ], JSON_UNESCAPED_UNICODE);

        $configs = [

            // ── General ───────────────────────────────────────────────────────
            ['group' => 'general', 'key' => 'site_name',           'value' => 'ملحمة ومعجنات الغدير'],
            ['group' => 'general', 'key' => 'restaurant_name_ar',  'value' => 'ملحمة ومعجنات الغدير'],
            ['group' => 'general', 'key' => 'restaurant_name_en',  'value' => 'Al-ghadeer Butchery & Bakery'],

            // ── Restaurant – identity ──────────────────────────────────────────
            ['group' => 'restaurant', 'key' => 'restaurant_logo',      'value' => null],
            ['group' => 'restaurant', 'key' => 'restaurant_phone',     'value' => '0983820430'],
            ['group' => 'restaurant', 'key' => 'restaurant_whatsapp',  'value' => '+963983820430'],

            // ── Restaurant – order control ────────────────────────────────
            ['group' => 'restaurant', 'key' => 'is_accepting_orders',            'value' => 'true'],
            ['group' => 'restaurant', 'key' => 'customer_cancel_before_minutes', 'value' => '60'],
            ['group' => 'restaurant', 'key' => 'order_closed_message',
             'value' => 'المطعم يستقبل التصفح حالياً، لكن الطلبات مغلقة خارج أوقات العمل.'],
            ['group' => 'restaurant', 'key' => 'delivery_note',
             'value' => 'أسعار التوصيل تقريبية ويحدد الموظف السعر النهائي بعد مراجعة الطلب.'],

            // ── Restaurant – scheduling ───────────────────────────────────
            ['group' => 'restaurant', 'key' => 'opening_hours',     'value' => $openingHours],
            ['group' => 'restaurant', 'key' => 'rejection_reasons', 'value' => $rejectionReasons],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value'], 'group' => $config['group']]
            );
        }
    }
}
