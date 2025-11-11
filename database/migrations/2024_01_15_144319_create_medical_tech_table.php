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
        Schema::create('medical_tech', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('highlight_image')->nullable();
            $table->json('reference_image')->nullable();
            $table->enum('spesific_gender', ['Male', 'Female', 'Unisex'])->nullable();
            $table->decimal('price', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_tech');
    }
};
