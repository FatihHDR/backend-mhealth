<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/packages_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $count = 0;
        while (($row = fgetcsv($h)) !== false) {
            $id = $row[0] ?? null;
            $title = $row[1] ?? null;
            $tagline = $row[2] ?? null;
            $highlight = $row[3] ?? null;
            $reference = $row[4] ?? null;
            $duration_by_day = $row[5] ?? null;
            $duration_by_night = $row[6] ?? null;
            $spesific_gender = $row[7] ?? null;
            $medical_content = $row[8] ?? null;
            $wellness_content = $row[9] ?? null;
            $included = $row[10] ?? null;
            $vendor_name = $row[11] ?? null;
            $hotel_name = $row[12] ?? null;
            $status = $row[13] ?? null;
            $price = $row[14] ?? null;

            $slug = Str::slug($title ?? Str::uuid());

            // try to resolve vendor_id and hotel_id by name or slug
            $vendor_id = null;
            if (!empty($vendor_name)) {
                $vendor_id = DB::table('vendor')->where('name', $vendor_name)->value('id')
                    ?? DB::table('vendor')->where('slug', Str::slug($vendor_name))->value('id');
            }

            $hotel_id = null;
            if (!empty($hotel_name)) {
                $hotel_id = DB::table('hotel')->where('name', $hotel_name)->value('id')
                    ?? DB::table('hotel')->where('slug', Str::slug($hotel_name))->value('id');
            }

            if (empty($vendor_id) || empty($hotel_id)) {
                $this->command->warn("Skipping package '{$title}' because vendor_id or hotel_id not found (vendor: '{$vendor_name}', hotel: '{$hotel_name}')");
                continue;
            }

            $map = [
                'slug' => $slug,
                'en_title' => $title,
                'id_title' => $title,
                'en_tagline' => $tagline,
                'id_tagline' => $tagline,
                'highlight_image' => $highlight,
                'reference_image' => $reference ? json_encode([$reference]) : null,
                'duration_by_day' => is_numeric($duration_by_day) ? (int)$duration_by_day : (int)preg_replace('/[^0-9]/', '', $duration_by_day ?: 0),
                'duration_by_night' => is_numeric($duration_by_night) ? (int)$duration_by_night : (empty($duration_by_night) ? null : (int)preg_replace('/[^0-9]/', '', $duration_by_night)),
                'spesific_gender' => $spesific_gender ?: 'Both',
                'en_medical_package_content' => $medical_content ?: '',
                'id_medical_package_content' => $medical_content ?: '',
                'en_wellness_package_content' => $wellness_content ?: '',
                'id_wellness_package_content' => $wellness_content ?: '',
                'included' => $included ? json_encode([$included]) : null,
                'vendor_id' => $vendor_id,
                'hotel_id' => $hotel_id,
                'real_price' => $price ?: null,
                'discount_price' => null,
                'status' => $status ?: 'draft',
            ];

            if (!empty($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $map['id'] = (string)$id;
            }

            try {
                DB::table('packages')->updateOrInsert(['slug' => $slug], $map);
                $count++;
            } catch (\Throwable $e) {
                $this->command->error("Failed to insert package '{$title}': " . $e->getMessage());
            }
        }

        fclose($h);
        $this->command->info("PackageListSeeder: processed {$count} rows");
    }
}