<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['area_name' => 'مزة خزان',   'estimated_fee' => 15000, 'sort_order' => 1],
            ['area_name' => 'مزة مدرسة',  'estimated_fee' => 10000, 'sort_order' => 2],
            ['area_name' => 'شيخ سعد',    'estimated_fee' => 25000, 'sort_order' => 3],
            ['area_name' => 'برامكة',     'estimated_fee' => 35000, 'sort_order' => 4],
        ];

        foreach ($zones as $data) {
            DeliveryZone::firstOrCreate(
                ['area_name' => $data['area_name']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}

