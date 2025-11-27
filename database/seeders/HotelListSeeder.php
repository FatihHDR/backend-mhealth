<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotelListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/hotel_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: id,name,description,location_map,logo,highlight_image
            $id = $row[0] ?? null;
            $name = $row[1] ?? null;
            $description = $row[2] ?? null;
            $location_map = $row[3] ?? null;
            $logo = $row[4] ?? null;
            $highlight_image = $row[5] ?? null;

            $map = [
                'slug' => Str::slug($name ?? Str::uuid()),
                'name' => $name,
                'en_description' => $description,
                'id_description' => $description,
                'location_map' => $location_map,
                'logo' => $logo,
                'highlight_image' => $highlight_image,
            ];

            // only include `id` when it's a valid UUID to avoid Postgres uuid cast errors
            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $map['id'] = (string) $id;
            }

            $rows[] = $map;
        }

        fclose($h);

        if (empty($rows)) return;

        try {
            // use updateOrInsert per-row to avoid requiring a DB-level unique index on `slug`
            foreach ($rows as $r) {
                $slug = $r['slug'] ?? null;
                if ($slug === null) continue;
                DB::table('hotel')->updateOrInsert(['slug' => $slug], $r);
            }
        } catch (\Throwable $e) {
            $this->command->error('HotelListSeeder failed: '.$e->getMessage());
        }
    }
}
