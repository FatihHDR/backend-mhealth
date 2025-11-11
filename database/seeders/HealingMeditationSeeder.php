<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HealingMeditationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = base_path('csv-files/sesi_healing_meditasi.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header row
        
        $packages = [];
        
        while (($row = fgetcsv($file)) !== false) {
            // Parse harga
            $hargaTotal = str_replace(['Rp ', '.', ',00'], '', $row[7]);
            $hargaTotal = (int) str_replace(' ', '', $hargaTotal);
            
            // Parse durasi paket (2 Days 1 Nights)
            $durasiPaket = $row[4];
            $durationDay = 0;
            $durationNight = 0;
            
            if (preg_match('/(\d+)\s*Day/i', $durasiPaket, $matches)) {
                $durationDay = (int) $matches[1];
            }
            if (preg_match('/(\d+)\s*Night/i', $durasiPaket, $matches)) {
                $durationNight = (int) $matches[1];
            }
            
            // Jika tidak ada durasi paket (row kedua), set durasi dari durasi sesi
            if (empty($durasiPaket)) {
                $durationDay = 0;
                $durationNight = 0;
            }
            
            $package = [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
                'title' => $row[1], // Sesi_Utama
                'description' => "Venue: {$row[0]}\nDurasi: {$row[3]}",
                'tagline' => $row[0], // Venue
                'highlight_image' => null,
                'reference_image' => null,
                'duration_by_day' => $durationDay,
                'duration_by_night' => $durationNight,
                'is_medical' => false,
                'is_entertain' => true,
                'medical_package' => null,
                'entertain_package' => json_encode([
                    'venue' => $row[0],
                    'session' => $row[1],
                    'session_price' => $row[2],
                    'session_duration' => $row[3],
                    'hotel' => $row[5],
                    'entertainment' => $row[6]
                ]),
                'included' => json_encode([
                    'hotel' => $row[5],
                    'entertainment' => $row[6],
                    'session' => $row[1]
                ]),
                'hotel_name' => !empty($row[5]) ? explode(' (', $row[5])[0] : null,
                'hotel_map' => null,
                'hospital_name' => null,
                'hospital_map' => null,
                'spesific_gender' => 'Unisex',
                'price' => $hargaTotal,
            ];
            
            $packages[] = $package;
        }
        
        fclose($file);
        
        // Insert data
        DB::table('package')->insert($packages);
        
        $this->command->info('Healing & Meditation packages seeded successfully!');
        $this->command->info('Total packages: ' . count($packages));
    }
}
