<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create 'laravel' schema for Supabase/PostgreSQL only
        // This separates our app data from Supabase's public schema (which is exposed as API)
        // Skip for SQLite as it doesn't support schemas
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS laravel');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop schema only for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP SCHEMA IF EXISTS laravel CASCADE');
        }
    }
};
