<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

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

        // map header name -> index for robust CSV parsing
        $headerMap = array_change_key_case(array_flip($headers));

        $count = 0;
        while (($row = fgetcsv($h)) !== false) {
            $get = function (string $key, $default = null) use ($row, $headerMap) {
                $k = strtolower($key);
                if (isset($headerMap[$k]) && array_key_exists($headerMap[$k], $row)) {
                    return $row[$headerMap[$k]];
                }
                return $default;
            };

            $id = $get('id', null);
            $title = $get('en_title', $get('title'));
            $tagline = $get('en_tagline', $get('tagline'));
            $highlight = $get('highlight_image', $get('highlight'));
            $reference = $get('reference_image', null);
            $duration_by_day = $get('duration_by_day', null);
            $duration_by_night = $get('duration_by_night', null);
            $spesific_gender = $get('spesific_gender', $get('specific_gender', null));
            $medical_content = $get('en_medical_package_content', $get('medical_content', null));
            $wellness_content = $get('en_wellness_package_content', $get('wellness_content', null));
            $included = $get('included', null);
            // vendor_id/hotel_id in CSV may be names or ids; original seeder attempted to match by name
            $vendor_name = $get('vendor_id', $get('vendor_name', null));
            $hotel_name = $get('hotel_id', $get('hotel_name', null));
            $status = $get('status', null);
            // price may be real_price or price
            $price = $get('real_price', $get('price', null));

            $slug = Str::slug($title ?? Str::uuid());

            // try to resolve vendor_id and hotel_id by name or slug
            $vendor_id = null;
            if (!empty($vendor_name)) {
                $vendor_id = DB::table('vendor')->where('name', $vendor_name)->value('id')
                    ?? DB::table('vendor')->where('slug', Str::slug($vendor_name))->value('id');
            }

            // If vendor not found but a name exists, auto-create a minimal vendor
            if (empty($vendor_id)) {
                $newVendorName = $vendor_name ?: "auto-vendor-{$slug}";
                $vendor_slug = Str::slug($newVendorName ?: "vendor-" . Str::uuid());
                $newVendorId = (string) Str::uuid();
                try {
                    DB::table('vendor')->insert([
                        'id' => $newVendorId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'slug' => $vendor_slug,
                        'name' => $newVendorName,
                        'en_description' => '',
                        'id_description' => '',
                        'category' => 'auto-generated',
                        'location_map' => null,
                        'specialist' => null,
                        'logo' => null,
                        'highlight_image' => '',
                        'reference_image' => null,
                    ]);
                    $vendor_id = $newVendorId;
                    $this->command->info("Created placeholder vendor '{$newVendorName}' ({$newVendorId})");
                } catch (\Throwable $e) {
                    $this->command->warn("Failed to create vendor '{$newVendorName}': " . $e->getMessage());
                }
            }

            $hotel_id = null;
            if (!empty($hotel_name)) {
                $hotel_id = DB::table('hotel')->where('name', $hotel_name)->value('id')
                    ?? DB::table('hotel')->where('slug', Str::slug($hotel_name))->value('id');
            }

            // If hotel not found but a name exists, auto-create a minimal hotel
            if (empty($hotel_id)) {
                $newHotelName = $hotel_name ?: "auto-hotel-{$slug}";
                $hotel_slug = Str::slug($newHotelName ?: "hotel-" . Str::uuid());
                $newHotelId = (string) Str::uuid();
                try {
                    DB::table('hotel')->insert([
                        'id' => $newHotelId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'slug' => $hotel_slug,
                        'name' => $newHotelName,
                        'en_description' => '',
                        'id_description' => '',
                        'location_map' => null,
                        'logo' => null,
                        'highlight_image' => '',
                        'reference_image' => null,
                    ]);
                    $hotel_id = $newHotelId;
                    $this->command->info("Created placeholder hotel '{$newHotelName}' ({$newHotelId})");
                } catch (\Throwable $e) {
                    $this->command->warn("Failed to create hotel '{$newHotelName}': " . $e->getMessage());
                }
            }

            // normalize gender: map common male/female values, treat 'both' or unknown as null
            $gender = null;
            if (!empty($spesific_gender) || $spesific_gender === '0') {
                $g = strtolower(trim((string)$spesific_gender));
                if (in_array($g, ['male', 'm', 'man', 'laki', 'laki-laki', 'pria'], true)) {
                    $gender = 'male';
                } elseif (in_array($g, ['female', 'f', 'woman', 'perempuan'], true)) {
                    $gender = 'female';
                } elseif (in_array($g, ['both', 'all', 'any'], true)) {
                    $gender = 'both';
                } else {
                    $gender = null;
                }
            }

            // DB column is NOT NULL for spesific_gender; default to 'both' if not provided
            if ($gender === null) {
                $gender = 'both';
            }

            // normalize status to a safe value and fallback to 'draft'
            $s = strtolower(trim((string)($status ?? '')));
            $allowedStatuses = ['draft', 'published', 'archived', 'active', 'inactive'];
            // treat open/close or unknown values as 'draft' to avoid enum errors
            if (in_array($s, $allowedStatuses, true)) {
                $status_value = $s;
            } else {
                $status_value = 'draft';
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
                // set null for unknown/both so Postgres enum isn't given an empty string
                'spesific_gender' => $gender,
                'en_medical_package_content' => $medical_content ?: '',
                'id_medical_package_content' => $medical_content ?: '',
                'en_wellness_package_content' => $wellness_content ?: '',
                'id_wellness_package_content' => $wellness_content ?: '',
                // try to normalize included: if it's already JSON array decode, otherwise wrap
                'included' => (function ($inc) {
                    if (empty($inc)) return null;
                    $decoded = @json_decode($inc, true);
                    if (is_array($decoded)) return json_encode($decoded);
                    // strip outer brackets/quotes and split by comma as fallback
                    $clean = trim($inc);
                    $clean = trim($clean, "[]\"'");
                    if ($clean === '') return null;
                    $parts = array_map('trim', explode(',', $clean));
                    return json_encode(array_filter($parts, fn($v) => $v !== ''));
                })($included),
                'vendor_id' => $vendor_id,
                'hotel_id' => $hotel_id,
                'real_price' => $price ?: null,
                'discount_price' => null,
                'status' => $status_value,
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