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

            // Skip if name is empty
            if (empty($name)) {
                continue;
            }

            // Build reference_image array as JSON
            $referenceImages = $image ? [$image] : [];

            $map = [
                'en_title' => $name,
                'id_title' => $name,
                'en_description' => $description ?: '',
                'id_description' => $description ?: '',
                'real_price' => $price ?: '0',
                'discount_price' => null,
                'spesific_gender' => 'both',
                'highlight_image' => $image ?: 'images/default-equipment.png',
                'reference_image' => json_encode($referenceImages), // Use json_encode for jsonb
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Generate slug
            $slug = Str::slug($name ?? Str::uuid());

            // Add ID if valid UUID
            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $map['id'] = (string) $id;
            }

            $rows[] = [
                'slug' => $slug,
                'id' => $map['id'] ?? null,
                'data' => $map
            ];
        }

        fclose($h);

        if (empty($rows)) {
            $this->command->warn('No medical equipment data to seed.');
            return;
        }

        try {
            foreach ($rows as $r) {
                // Check if record with this ID or slug already exists
                if (!empty($r['id'])) {
                    $existing = DB::table('medical_equipment')->where('id', $r['id'])->first();
                    
                    if ($existing) {
                        // Update existing record
                        $updateData = $r['data'];
                        unset($updateData['id']); // Don't update primary key
                        
                        DB::table('medical_equipment')
                            ->where('id', $r['id'])
                            ->update($updateData);
                        
                        $this->command->info("Updated: {$r['slug']}");
                    } else {
                        // Insert new record
                        DB::table('medical_equipment')->insert($r['data']);
                        $this->command->info("Inserted: {$r['slug']}");
                    }
                } else {
                    // No ID provided, use updateOrInsert with slug
                    DB::table('medical_equipment')->updateOrInsert(
                        ['slug' => $r['slug']], 
                        $r['data']
                    );
                    $this->command->info("Upserted: {$r['slug']}");
                }
            }
            
            $this->command->info('MedicalEquipmentListSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('MedicalEquipmentListSeeder failed: '.$e->getMessage());
            throw $e;
        }
    }
}
