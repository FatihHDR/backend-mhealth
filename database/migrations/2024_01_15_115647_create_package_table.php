<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('package', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('tagline')->nullable();
            $table->string('highlight_image')->nullable();
            $table->json('reference_image')->nullable();
            $table->integer('duration_by_day')->default(0);
            $table->integer('duration_by_night')->default(0);
            $table->boolean('is_medical')->default(false);
            $table->boolean('is_entertain')->default(false);
            $table->json('medical_package')->nullable();
            $table->json('entertain_package')->nullable();
            $table->json('included')->nullable();
            $table->string('hotel_name')->nullable();
            $table->string('hotel_map')->nullable();
            $table->string('hospital_name')->nullable();
            $table->string('hospital_map')->nullable();
            $table->enum('spesific_gender', ['Male', 'Female', 'Unisex'])->nullable();
            $table->decimal('price', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package');
    }
};
