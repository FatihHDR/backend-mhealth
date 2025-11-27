<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AboutUsSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/about_us.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            // Map CSV columns to DB columns
            $map = [];
            // CSV headers expected: title, about_content, brand_tagline
            $title = $row[0] ?? null;
            $about = $row[1] ?? null;
            $brand = $row[2] ?? null;

            $map['en_title'] = $title;
            $map['id_title'] = $title;
            $map['en_about_content'] = $about;
            $map['id_about_content'] = $about;
            $map['en_brand_tagline'] = $brand;
            $map['id_brand_tagline'] = $brand;

            $rows[] = $map;
        }

        fclose($h);

        // Truncate and insert to ensure overwrite (table small and simple)
        try {
            DB::table('about_us')->truncate();
            if (! empty($rows)) {
                DB::table('about_us')->insert($rows);
            }
        } catch (\Throwable $e) {
            $this->command->error('AboutUsSeeder failed: '.$e->getMessage());
        }
    }
}
