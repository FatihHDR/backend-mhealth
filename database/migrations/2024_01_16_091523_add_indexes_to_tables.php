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
        // Add indexes for better query performance
        Schema::table('payment', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('package', function (Blueprint $table) {
            $table->index('is_medical');
            $table->index('is_entertain');
            $table->index('spesific_gender');
        });

        Schema::table('medical_tech', function (Blueprint $table) {
            $table->index('spesific_gender');
        });

        Schema::table('chatbot', function (Blueprint $table) {
            $table->index('public_token');
            $table->index('status');
        });

        Schema::table('article', function (Blueprint $table) {
            $table->index('category');
            $table->index('created_at');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index('start_date');
            $table->index('end_date');
        });

        Schema::table('hospital_relation', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('package', function (Blueprint $table) {
            $table->dropIndex(['is_medical']);
            $table->dropIndex(['is_entertain']);
            $table->dropIndex(['spesific_gender']);
        });

        Schema::table('medical_tech', function (Blueprint $table) {
            $table->dropIndex(['spesific_gender']);
        });

        Schema::table('chatbot', function (Blueprint $table) {
            $table->dropIndex(['public_token']);
            $table->dropIndex(['status']);
        });

        Schema::table('article', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
        });

        Schema::table('hospital_relation', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
