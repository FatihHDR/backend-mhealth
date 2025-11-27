<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalEquipmentListSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('csv-files/medical_equipment_list.csv');
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
                DB::table('medical_equipment')->insert($data);
            } catch (\Throwable $e) {
                $this->command->error('MedicalEquipmentListSeeder skip row: '.$e->getMessage());
            }
        }

        fclose($h);
    }
}
