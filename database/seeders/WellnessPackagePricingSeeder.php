<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WellnessPackagePricingSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/wellness_package_pricing.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        $defaultVendorId = DB::table('vendor')->value('id');
        $defaultHotelId = DB::table('hotel')->value('id');

        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: id,name,price,duration,hospital_id
            $id = $row[0] ?? null;
            $name = $row[1] ?? null;
            $price = $row[2] ?? null;
            $duration_text = $row[3] ?? null; // Contains text, not a number of days
            $hospital_id = $row[4] ?? null;

            $vendor_id = null;
            if (!empty($hospital_id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $hospital_id)) {
                // Check if this hospital_id exists in the vendor table
                if (DB::table('vendor')->where('id', $hospital_id)->exists()) {
                    $vendor_id = $hospital_id;
                }
            }
            if (empty($vendor_id)) {
                $vendor_id = $defaultVendorId;
            }

            $map = [
                'slug' => Str::slug($name ?? Str::uuid()),
                'en_title' => $name,
                'id_title' => $name,
                'real_price' => $price,
                'duration_by_day' => 1, // Default value, as duration from csv is text
                'duration_by_night' => 0,
                'vendor_id' => $vendor_id,
                'hotel_id' => $defaultHotelId,
                'en_tagline' => 'Wellness Package',
                'id_tagline' => 'Paket Wellness',
                'highlight_image' => 'images/default-package.png',
                'spesific_gender' => 'both',
                'en_medical_package_content' => 'N/A',
                'id_medical_package_content' => 'N/A',
                'en_wellness_package_content' => $duration_text ?: 'Wellness activities',
                'id_wellness_package_content' => $duration_text ?: 'Aktivitas wellness',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $map['id'] = (string) $id;
            }

            $rows[] = $map;
        }

        fclose($h);

        if (empty($rows)) return;

        try {
            foreach ($rows as $r) {
                $slug = $r['slug'] ?? null;
                if ($slug === null) {
                    continue;
                }
                $update = $r;
                unset($update['slug']);
                DB::table('packages')->updateOrInsert(['slug' => $slug], $update);
            }
            $this->command->info('WellnessPackagePricingSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('WellnessPackagePricingSeeder failed: '.$e->getMessage());
        }
    }
}
