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
        // Create 'laravel' schema for Supabase
        // This separates our app data from Supabase's public schema (which is exposed as API)
        DB::statement('CREATE SCHEMA IF NOT EXISTS laravel');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS laravel CASCADE');
    }
};
