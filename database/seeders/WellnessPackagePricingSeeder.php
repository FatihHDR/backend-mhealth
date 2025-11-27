<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WellnessPackagePricingSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/wellness_package_pricing.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        while (($row = fgetcsv($h)) !== false) {
            $data = [];
            foreach ($headers as $i => $col) {
                $data[$col] = $row[$i] ?? null;
            }
            try {
                // pricing usually belongs to packages or wellness; insert into `packages` to preserve compatibility
                DB::table('packages')->insert($data);
            } catch (\Throwable $e) {
                $this->command->error('WellnessPackagePricingSeeder skip row: '.$e->getMessage());
            }
        }

        fclose($h);
    }
}
