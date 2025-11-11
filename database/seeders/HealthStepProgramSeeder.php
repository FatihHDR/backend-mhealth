<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HealthStepProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = base_path('csv-files/program_langkah_kesehatan.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header row
        
        $packages = [];
        
        while (($row = fgetcsv($file)) !== false) {
            // Parse target langkah
            $targetLangkah = str_replace(['.', ' langkah'], '', $row[3]);
            $targetLangkah = !empty($targetLangkah) ? (int) $targetLangkah : 0;
            
            // Tentukan harga berdasarkan hari (ini bisa disesuaikan)
            $basePrice = 50000; // Base price per day
            $price = $basePrice * (int) $row[0]; // Multiply by day number
            
            $package = [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
                'title' => "Day {$row[0]}: {$row[1]}", // Day + Topic
                'description' => "Rute: {$row[2]}\nTarget: {$row[3]}\nAktivitas: {$row[4]}\n\nTip: {$row[5]}",
                'tagline' => $row[1], // Topic
                'highlight_image' => null,
                'reference_image' => null,
                'duration_by_day' => 1,
                'duration_by_night' => 0,
                'is_medical' => true,
                'is_entertain' => true,
                'medical_package' => json_encode([
                    'day' => $row[0],
                    'target_steps' => $targetLangkah,
                    'health_check' => $row[0] == 1 ? 'Yes' : 'No',
                    'route' => $row[2],
                ]),
                'entertain_package' => json_encode([
                    'route' => $row[2],
                    'activity' => $row[4],
                    'location' => $row[2]
                ]),
                'included' => json_encode([
                    'activity' => $row[4],
                    'route_guide' => $row[2],
                    'health_monitoring' => 'Smartwatch tracking',
                    'doctor_tips' => $row[5]
                ]),
                'hotel_name' => null,
                'hotel_map' => null,
                'hospital_name' => null,
                'hospital_map' => null,
                'spesific_gender' => 'Unisex',
                'price' => $price,
            ];
            
            $packages[] = $package;
        }
        
        fclose($file);
        
        // Insert data
        DB::table('package')->insert($packages);
        
        $this->command->info('Health Step Program packages seeded successfully!');
        $this->command->info('Total packages: ' . count($packages));
    }
}
