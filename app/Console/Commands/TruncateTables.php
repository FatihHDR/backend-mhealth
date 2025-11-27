<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateTables extends Command
{
    protected $signature = 'db:truncate {table?}';
    protected $description = 'Truncate specific table or all seeded tables';

    public function handle()
    {
        $table = $this->argument('table');
        
        // Tables to truncate (in order to respect FK constraints)
        $tables = [
            'wellness',
            'medical_equipment',
            'medical',
            'packages',
            'events',
            'payment_records',
            'consult_schedule',
            'article',
            'vendor',
            'hotel',
        ];

        if ($table) {
            // Truncate single table
            $this->truncateTable($table);
        } else {
            // Truncate all tables
            if (!$this->confirm('This will truncate all seeded tables. Continue?')) {
                return 0;
            }

            // Disable foreign key checks (PostgreSQL)
            DB::statement('SET CONSTRAINTS ALL DEFERRED');

            foreach ($tables as $tbl) {
                $this->truncateTable($tbl);
            }

            // Re-enable foreign key checks
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        }

        $this->info('Done!');
        return 0;
    }

    private function truncateTable($table)
    {
        try {
            DB::table($table)->truncate();
            $this->info("Table '{$table}' truncated.");
        } catch (\Exception $e) {
            $this->error("Failed to truncate '{$table}': " . $e->getMessage());
        }
    }
}
