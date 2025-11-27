<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LatestPackageSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure at least one vendor exists, create one if not.
        if (DB::table('vendor')->count() === 0) {
            DB::table('vendor')->insert([
                'id' => Str::uuid()->toString(),
                'slug' => 'default-vendor',
                'name' => 'Default Vendor',
                'en_description' => 'Default vendor description',
                'id_description' => 'Default vendor description',
                'category' => 'General',
                'highlight_image' => 'images/default-vendor.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure at least one hotel exists, create one if not.
        if (DB::table('hotel')->count() === 0) {
            DB::table('hotel')->insert([
                'id' => Str::uuid()->toString(),
                'slug' => 'default-hotel',
                'name' => 'Default Hotel',
                'en_description' => 'Default hotel description',
                'id_description' => 'Default hotel description',
                'highlight_image' => 'images/default-hotel.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $csvFile = base_path('csv-files/latest_package.csv');

        if (! file_exists($csvFile)) {
            $this->command->error('CSV file not found: ' . $csvFile);
            return;
        }

        $fp = fopen($csvFile, 'r');
        $headers = fgetcsv($fp);


        $rows = [];
        while (($row = fgetcsv($fp)) !== false) {
            // Expected CSV headers (observed): id,package_name,tagline,medical_contents,entertainment_contents,price,duration,specific_gender,hotel_name,vendor_name,created_at,updated_at
            $id = $row[array_search('id', $headers)] ?? null;
            $package_name = $row[array_search('package_name', $headers)] ?? ($row[0] ?? null);
            $tagline = $row[array_search('tagline', $headers)] ?? null;
            $medical_contents = $row[array_search('medical_contents', $headers)] ?? null;
            $entertainment_contents = $row[array_search('entertainment_contents', $headers)] ?? null;
            $price = $row[array_search('price', $headers)] ?? null;
            $duration_raw = $row[array_search('duration', $headers)] ?? null;
            $specific_gender = $row[array_search('specific_gender', $headers)] ?? null;
            $hotel_name = $row[array_search('hotel_name', $headers)] ?? null;
            $vendor_name = $row[array_search('vendor_name', $headers)] ?? null;

            // parse duration: try to extract first two numbers as days and nights
            $duration_by_day = null;
            $duration_by_night = null;
            if (!empty($duration_raw)) {
                preg_match_all('/(\d+)/', $duration_raw, $m);
                if (!empty($m[1][0])) $duration_by_day = (int)$m[1][0];
                if (!empty($m[1][1])) $duration_by_night = (int)$m[1][1];
            }

            // try to resolve vendor_id and hotel_id by name/slug if possible
            $vendor_id = null;
            if (!empty($vendor_name)) {
                $vendor_id = DB::table('vendor')->where('name', $vendor_name)->value('id')
                    ?? DB::table('vendor')->where('slug', Str::slug($vendor_name))->value('id');
            }
            if (empty($vendor_id)) {
                $vendor_id = DB::table('vendor')->value('id');
            }

            $hotel_id = null;
            if (!empty($hotel_name)) {
                $hotel_id = DB::table('hotel')->where('name', $hotel_name)->value('id')
                    ?? DB::table('hotel')->where('slug', Str::slug($hotel_name))->value('id');
            }
            if (empty($hotel_id)) {
                $hotel_id = DB::table('hotel')->value('id');
            }

            $data = [
                'slug' => Str::slug($package_name ?? Str::uuid()),
                'en_title' => $package_name,
                'id_title' => $package_name,
                'en_tagline' => $tagline,
                'id_tagline' => $tagline,
                'highlight_image' => 'images/trastmed-cover.png',
                'en_medical_package_content' => $medical_contents,
                'id_medical_package_content' => $medical_contents,
                'en_wellness_package_content' => $entertainment_contents,
                'id_wellness_package_content' => $entertainment_contents,
                'real_price' => $price,
                'spesific_gender' => $specific_gender,
                'duration_by_day' => $duration_by_day,
                'duration_by_night' => $duration_by_night,
                'vendor_id' => $vendor_id,
                'hotel_id' => $hotel_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // only include id when it's a valid UUID
            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $data['id'] = (string)$id;
            }

            $rows[] = $data;
        }

        fclose($fp);

        if (empty($rows)) {
            $this->command->info('LatestPackageSeeder: no rows found');
            return;
        }

        // Insert/update rows one by one to avoid relying on DB-level unique indexes
        try {
            foreach ($rows as $r) {
                $slug = $r['slug'] ?? null;
                if ($slug === null) continue;
                // remove `slug` from update set because updateOrInsert takes where-values and update-values
                $update = $r;
                unset($update['slug']);
                DB::table('packages')->updateOrInsert(['slug' => $slug], $update);
            }
            $this->command->info('LatestPackageSeeder finished');
        } catch (\Throwable $e) {
            $this->command->error('LatestPackageSeeder failed: ' . $e->getMessage());
        }
    }
}
