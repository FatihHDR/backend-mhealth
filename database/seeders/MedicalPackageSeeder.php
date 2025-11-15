<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalPackageSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = base_path('csv-files/medical.csv');

        if (! file_exists($csvFile)) {
            $this->command->error('CSV file not found!');

            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            $hospitalName = trim($row[0]);
            $title = trim($row[1]);
            $description = trim($row[2]);
            $priceStr = trim($row[3]);

            // Convert price from "Rp 35.500.000,00" to 35500000
            $price = $this->parsePrice($priceStr);

            // Determine if medical or entertainment
            $isMedical = true;
            $isEntertain = false;

            // Determine gender specificity
            $specificGender = $this->determineGender($title, $description);

            DB::table('package')->insert([
                'id' => Str::uuid(),
                'title' => $title,
                'description' => $description,
                'tagline' => $this->generateTagline($title),
                'highlight_image' => null,
                'reference_image' => null,
                'duration_by_day' => $this->extractDuration($description)['day'],
                'duration_by_night' => $this->extractDuration($description)['night'],
                'is_medical' => $isMedical,
                'is_entertain' => $isEntertain,
                'medical_package' => json_encode($this->extractMedicalDetails($description)),
                'entertain_package' => null,
                'included' => json_encode($this->extractIncluded($description)),
                'hotel_name' => null,
                'hotel_map' => null,
                'hospital_name' => $hospitalName,
                'hospital_map' => 'https://maps.google.com/?q='.urlencode($hospitalName),
                'spesific_gender' => $specificGender,
                'price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);

        $this->command->info('Medical packages seeded successfully!');
    }

    private function parsePrice($priceStr)
    {
        // Remove "Rp", dots, commas and convert to integer
        $price = preg_replace('/[Rp\s\.]/', '', $priceStr);
        $price = str_replace(',00', '', $price);

        return (int) $price;
    }

    private function determineGender($title, $description)
    {
        $title = strtolower($title);
        $description = strtolower($description);

        if (strpos($title, 'woman') !== false || strpos($title, 'female') !== false ||
            strpos($description, 'women') !== false || strpos($description, 'pregnancy') !== false ||
            strpos($description, 'mammogram') !== false || strpos($description, 'pap smear') !== false) {
            return 'Female';
        }

        if (strpos($title, 'man') !== false || strpos($title, 'male') !== false ||
            strpos($description, 'men') !== false) {
            return 'Male';
        }

        return 'Unisex';
    }

    private function generateTagline($title)
    {
        $taglines = [
            'Your Health, Our Priority',
            'Advanced Medical Care for Better Life',
            'Quality Healthcare You Can Trust',
            'Professional Medical Services',
            'Comprehensive Health Solutions',
        ];

        return $taglines[array_rand($taglines)];
    }

    private function extractDuration($description)
    {
        // Default duration
        $duration = ['day' => 1, 'night' => 0];

        // Try to extract from description
        if (preg_match('/(\d+)\s*days?\s*\/?\s*(\d+)?\s*nights?/i', $description, $matches)) {
            $duration['day'] = (int) $matches[1];
            $duration['night'] = isset($matches[2]) ? (int) $matches[2] : 0;
        }

        return $duration;
    }

    private function extractMedicalDetails($description)
    {
        $lines = explode("\n", $description);
        $details = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d+\./', $line)) {
                $details[] = $line;
            }
        }

        return $details;
    }

    private function extractIncluded($description)
    {
        // Extract what's included in the package
        $included = [];

        if (strpos($description, 'Doctor consultation') !== false) {
            $included[] = 'Doctor Consultation';
        }
        if (strpos($description, 'test') !== false || strpos($description, 'Test') !== false) {
            $included[] = 'Medical Tests';
        }
        if (strpos($description, 'hospital stay') !== false) {
            $included[] = 'Hospital Stay';
        }

        return $included;
    }
}
