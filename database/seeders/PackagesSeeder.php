<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Packages;
use App\Models\Vendor;
use App\Models\Hotel;

class PackagesSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing vendors and hotels
        $vendorIds = Vendor::pluck('id')->toArray();
        $hotelIds = Hotel::pluck('id')->toArray();

        if (empty($vendorIds) || empty($hotelIds)) {
            $this->command->warn('PackagesSeeder: No vendors or hotels found. Please seed vendors and hotels first.');
            return;
        }

        // create 20 sample packages with random vendor and hotel
        Packages::factory()->count(20)->create([
            'vendor_id' => fn () => $vendorIds[array_rand($vendorIds)],
            'hotel_id' => fn () => $hotelIds[array_rand($hotelIds)],
        ]);

        $this->command->info('PackagesSeeder: created 20 dummy packages');
    }
}
