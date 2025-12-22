<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WellnessListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/wellness_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        // Get all hotels to use their IDs
        $hotels = DB::table('hotel')->get(['id', 'name', 'slug']);
        
        if ($hotels->isEmpty()) {
            $this->command->error('No hotels found. Please seed hotels first.');
            return;
        }

        // Get first vendor as default
        $defaultVendorId = DB::table('vendor')->value('id');
        
        if (!$defaultVendorId) {
            $this->command->error('No vendor found. Please seed vendors first.');
            return;
        }

        $rows = [];
        $hotelIndex = 0;
        $processedHotelIds = []; // Track processed hotels to avoid duplicates

        while (($row = fgetcsv($h)) !== false) {
            // CSV headers: id,hospital_name,description,location_map,logo,highlight_image
            $csvId = $row[0] ?? null;
            $name = $row[1] ?? null;
            $description = $row[2] ?? null;
            $highlight_image = $row[5] ?? null;

            // Find matching hotel by name, or use next available hotel
            $hotel = null;
            if (!empty($name)) {
                $hotel = $hotels->first(function($h) use ($name) {
                    return stripos($h->name, $name) !== false || stripos($name, $h->name) !== false;
                });
            }
            
            // If no match found, cycle through hotels
            if (!$hotel && $hotelIndex < $hotels->count()) {
                $hotel = $hotels[$hotelIndex];
                $hotelIndex++;
            } else if (!$hotel) {
                $hotel = $hotels->first(); // Fallback to first hotel
            }

            // Skip if this hotel ID has already been processed
            if (in_array($hotel->id, $processedHotelIds)) {
                $this->command->warn("Skipping duplicate hotel: {$hotel->name}");
                continue;
            }

            // Mark this hotel as processed
            $processedHotelIds[] = $hotel->id;

            // Find matching vendor for hotel_id
            $vendorId = DB::table('vendor')
                ->where('name', 'ILIKE', '%' . $hotel->name . '%')
                ->value('id');
            
            if (!$vendorId) {
                $vendorId = $defaultVendorId;
            }

            $map = [
                'id' => $hotel->id, // Use hotel's ID as wellness ID (FK constraint)
                'en_title' => $name,
                'id_title' => $name,
                'en_tagline' => $description,
                'id_tagline' => $description,
                'highlight_image' => $highlight_image ?: 'images/default-wellness.png',
                'reference_image' => json_encode([]), // Encode to JSON for jsonb column
                'duration_by_day' => 1,
                'duration_by_night' => null,
                'spesific_gender' => 'both',
                'en_wellness_package_content' => $description,
                'id_wellness_package_content' => $description,
                'included' => json_encode([]), // Encode to JSON for jsonb column
                'hotel_id' => $vendorId, // This references vendor table
                'real_price' => '100000',
                'discount_price' => null,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Generate slug
            $slug = Str::slug($name ?? $hotel->slug);

            // Ensure slug is included in the data being inserted
            $map['slug'] = $slug;

            $rows[] = [
                'slug' => $slug,
                'id' => $hotel->id,
                'data' => $map
            ];
        }

        fclose($h);

        if (empty($rows)) {
            $this->command->warn('No wellness data to seed.');
            return;
        }

        try {
            foreach ($rows as $r) {
                // Check if wellness with this ID already exists
                $existing = DB::table('wellness')->where('id', $r['id'])->first();
                
                if ($existing) {
                    // Update existing record (exclude 'id' from update data)
                    $updateData = $r['data'];
                    unset($updateData['id']); // Don't update primary key
                    
                    DB::table('wellness')
                        ->where('id', $r['id'])
                        ->update($updateData);
                    
                    $this->command->info("Updated wellness: {$r['slug']}");
                } else {
                    // Insert new record
                    DB::table('wellness')->insert($r['data']);
                    $this->command->info("Inserted wellness: {$r['slug']}");
                }
            }
            
            $this->command->info('WellnessListSeeder finished successfully.');
        } catch (\Throwable $e) {
            $this->command->error('WellnessListSeeder failed: '.$e->getMessage());
            throw $e; // Re-throw to see full stack trace
        }
    }
}
