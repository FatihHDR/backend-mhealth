<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/vendor_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: id,name,description,logo,location_map
            $id = $row[0] ?? null;
            $name = $row[1] ?? null;
            $description = $row[2] ?? null;
            $logo = $row[3] ?? null;
            $location_map = $row[4] ?? null;

            $map = [
                'slug' => Str::slug($name ?? Str::uuid()),
                'name' => $name,
                'en_description' => $description,
                'id_description' => $description,
                'logo' => $logo,
                'location_map' => $location_map,
                'category' => 'General', // Default category
                'highlight_image' => 'images/default-vendor.png', // Default image
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
                DB::table('vendor')->updateOrInsert(['slug' => $slug], $update);
            }
            $this->command->info('VendorListSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('VendorListSeeder failed: '.$e->getMessage());
        }
    }
}
