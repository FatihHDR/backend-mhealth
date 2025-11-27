<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/medical.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        $defaultVendorId = DB::table('vendor')->value('id');

        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: vendor_name,package_name,price
            $vendor_name = $row[0] ?? null;
            $package_name = $row[1] ?? null;
            $price = $row[2] ?? null;

            $vendor_id = null;
            if (!empty($vendor_name)) {
                $vendor_id = DB::table('vendor')->where('name', $vendor_name)->value('id');
            }
            if (empty($vendor_id)) {
                $vendor_id = $defaultVendorId;
            }

            $map = [
                'slug' => Str::slug($package_name ?? Str::uuid()),
                'vendor_id' => $vendor_id,
                'en_title' => $package_name,
                'id_title' => $package_name,
                'real_price' => $price,
                'en_tagline' => $package_name, // Use package name as default
                'id_tagline' => $package_name, // Use package name as default
                'highlight_image' => 'images/default-medical.png',
                'duration_by_day' => 1,
                'spesific_gender' => 'both',
                'en_medical_package_content' => 'Details for ' . $package_name,
                'id_medical_package_content' => 'Details for ' . $package_name,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];

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
                DB::table('medical')->updateOrInsert(['slug' => $slug], $update);
            }
            $this->command->info('MedicalSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('MedicalSeeder failed: '.$e->getMessage());
        }
    }
}
