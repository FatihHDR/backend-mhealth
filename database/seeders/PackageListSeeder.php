<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/package_list.csv');
        if (!is_readable($file)) {
            $this->command->info("CSV not found: {$file}");
            return;
        }

        if (($h = fopen($file, 'r')) === false) return;
        $headers = fgetcsv($h) ?: [];

        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            $data = [];
            foreach ($headers as $i => $col) {
                $data[$col] = $row[$i] ?? null;
            }
            $rows[] = $data;
        }

        fclose($h);

        if (empty($rows)) return;

        try {
            DB::table('packages')->upsert($rows, ['slug']);
        } catch (\Throwable $e) {
            $this->command->error('PackageListSeeder failed: '.$e->getMessage());
        }
    }
}
