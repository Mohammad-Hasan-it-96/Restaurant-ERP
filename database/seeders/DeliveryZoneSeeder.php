<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        // Delivery zones are entirely location-specific, so none are seeded.
        // Each restaurant adds its own areas + fees at /admin/delivery-zones.
        $zones = [];

        foreach ($zones as $data) {
            DeliveryZone::firstOrCreate(
                ['area_name' => $data['area_name']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
