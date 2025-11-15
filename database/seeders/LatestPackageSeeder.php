<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LatestPackageSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = base_path('csv-files/latest_package.csv');

        if (! file_exists($csvFile)) {
            $this->command->error('CSV file not found!');

            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 9) {
                continue;
            }

            $hospitalName = trim($row[0]);
            $packageName = trim($row[1]);
            $medical = trim($row[2]);
            $tagline = trim($row[3]);
            $duration = trim($row[4]);
            $hotels = trim($row[5]);
            $entertain = trim($row[6]);
            $gender = trim($row[7]);
            $priceStr = trim($row[8]);

            // Parse duration
            $durationData = $this->parseDuration($duration);

            // Parse price
            $price = $this->parsePrice($priceStr);

            // Parse hotel info
            $hotelData = $this->parseHotel($hotels);

            // Determine gender
            $specificGender = $this->determineGender($gender);

            DB::table('package')->insert([
                'id' => Str::uuid(),
                'title' => $packageName,
                'description' => $medical,
                'tagline' => ! empty($tagline) ? $tagline : 'Premium Medical Tourism Package',
                'highlight_image' => null,
                'reference_image' => null,
                'duration_by_day' => $durationData['day'],
                'duration_by_night' => $durationData['night'],
                'is_medical' => true,
                'is_entertain' => ! empty($entertain),
                'medical_package' => json_encode($this->parseMedicalPackage($medical)),
                'entertain_package' => ! empty($entertain) ? json_encode($this->parseEntertainPackage($entertain)) : null,
                'included' => json_encode($this->extractIncluded($medical, $entertain)),
                'hotel_name' => $hotelData['name'],
                'hotel_map' => $hotelData['map'],
                'hospital_name' => $hospitalName,
                'hospital_map' => 'https://maps.google.com/?q='.urlencode($hospitalName),
                'spesific_gender' => $specificGender,
                'price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);

        $this->command->info('Latest packages seeded successfully!');
    }

    private function parsePrice($priceStr)
    {
        if (empty($priceStr)) {
            return 0;
        }

        // Remove "Rp", dots, commas and convert to integer
        $price = preg_replace('/[Rp\s\.]/', '', $priceStr);
        $price = str_replace(',00', '', $price);

        return (int) $price;
    }

    private function parseDuration($duration)
    {
        $result = ['day' => 1, 'night' => 0];

        if (preg_match('/(\d+)\s*days?\s*\/?\s*(\d+)?\s*nights?/i', $duration, $matches)) {
            $result['day'] = (int) $matches[1];
            $result['night'] = isset($matches[2]) ? (int) $matches[2] : 0;
        }

        return $result;
    }

    private function parseHotel($hotels)
    {
        $result = ['name' => null, 'map' => null];

        if (empty($hotels)) {
            return $result;
        }

        // Extract hotel name (before parenthesis)
        if (preg_match('/^([^(]+)/', $hotels, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['map'] = 'https://maps.google.com/?q='.urlencode($result['name']);
        }

        return $result;
    }

    private function determineGender($gender)
    {
        $gender = strtolower(trim($gender));

        if (strpos($gender, 'male') !== false && strpos($gender, 'female') === false) {
            return 'Male';
        }

        if (strpos($gender, 'female') !== false) {
            return 'Female';
        }

        return 'Unisex';
    }

    private function parseMedicalPackage($medical)
    {
        if (empty($medical)) {
            return [];
        }

        $lines = array_filter(array_map('trim', explode("\n", $medical)));

        return array_values($lines);
    }

    private function parseEntertainPackage($entertain)
    {
        if (empty($entertain)) {
            return [];
        }

        $lines = array_filter(array_map('trim', explode("\n", $entertain)));

        return array_values($lines);
    }

    private function extractIncluded($medical, $entertain)
    {
        $included = [];

        if (! empty($medical)) {
            $included[] = 'Medical Check-Up';
            $included[] = 'Doctor Consultation';
        }

        if (! empty($entertain)) {
            $included[] = 'Entertainment Activities';
            $included[] = 'Tourism Package';
        }

        $included[] = 'Hotel Accommodation';

        return $included;
    }
}
