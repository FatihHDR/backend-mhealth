<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LatestPackageSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = base_path('csv-files/latest_package.csv');

        if (! file_exists($csvFile)) {
            $this->command->error('CSV file not found: ' . $csvFile);
            return;
        }

        $fp = fopen($csvFile, 'r');
        $headers = fgetcsv($fp);

        while (($row = fgetcsv($fp)) !== false) {
            // flexible mapping: map headers to values, then attempt to insert into `packages`
            $data = [];
            if (is_array($headers)) {
                foreach ($headers as $i => $col) {
                    $data[$col] = $row[$i] ?? null;
                }
            } else {
                // fallback: put first columns into some common fields
                $data = [
                    'slug' => Str::slug($row[0] ?? Str::uuid()),
                    'en_title' => $row[1] ?? null,
                    'en_tagline' => $row[2] ?? null,
                ];
            }

            // ensure required system fields
            $data['id'] = $data['id'] ?? (string) Str::uuid();
            $data['created_at'] = $data['created_at'] ?? now();
            $data['updated_at'] = $data['updated_at'] ?? now();

            try {
                DB::table('packages')->insert($data);
            } catch (\Throwable $e) {
                $this->command->error('LatestPackageSeeder: skipped row - ' . $e->getMessage());
            }
        }

        fclose($fp);

        $this->command->info('LatestPackageSeeder finished');
    }
}
