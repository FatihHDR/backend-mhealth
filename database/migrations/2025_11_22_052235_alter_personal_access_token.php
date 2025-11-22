<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change `tokenable_id` to a string type so UUID primary keys work.
     * Uses raw SQL to avoid requiring doctrine/dbal.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config('database.connections.' . ($connection ?? '') . '.driver');

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE varchar USING tokenable_id::varchar;");
        } elseif ($driver === 'mysql' || $driver === 'mysqli') {
            DB::statement("ALTER TABLE personal_access_tokens MODIFY tokenable_id varchar(255);");
        } else {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->string('tokenable_id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     * Converts the column back to bigint when possible.
     */
    public function down(): void
    {
        $connection = config('database.default');
        $driver = config('database.connections.' . ($connection ?? '') . '.driver');

        if ($driver === 'pgsql') {
            // Try converting back to bigint using a safe cast; this may fail if values are non-numeric.
            DB::statement("ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING tokenable_id::bigint;");
        } elseif ($driver === 'mysql' || $driver === 'mysqli') {
            DB::statement("ALTER TABLE personal_access_tokens MODIFY tokenable_id bigint;");
        } else {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('tokenable_id')->change();
            });
        }
    }
};
