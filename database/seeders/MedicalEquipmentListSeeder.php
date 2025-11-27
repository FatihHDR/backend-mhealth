<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalEquipmentListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/medical_equipment_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: id,name,description,price,image
            $id = $row[0] ?? null;
            $name = $row[1] ?? null;
            $description = $row[2] ?? null;
            $price = $row[3] ?? null;
            $image = $row[4] ?? null;

            // Build reference_image array
            $referenceImages = $image ? [$image] : [];

            $map = [
                'en_title' => $name,
                'id_title' => $name,
                'en_description' => $description,
                'id_description' => $description,
                'real_price' => $price,
                'spesific_gender' => 'both',
                'highlight_image' => $image ?: '',
                'reference_image' => DB::raw("ARRAY[" . ($image ? "'" . str_replace("'", "''", $image) . "'" : "") . "]::text[]"),
                'status' => 'draft',
            ];

            // Generate slug
            $slug = Str::slug($name ?? Str::uuid());

            // Add ID if valid UUID
            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $map['id'] = (string) $id;
            }

            $rows[] = [
                'slug' => $slug,
                'data' => $map
            ];
        }

        fclose($h);

        if (empty($rows)) return;

        try {
            foreach ($rows as $r) {
                DB::table('medical_equipment')->updateOrInsert(
                    ['slug' => $r['slug']], 
                    $r['data']
                );
            }
            $this->command->info('MedicalEquipmentListSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('MedicalEquipmentListSeeder failed: '.$e->getMessage());
        }
    }
}