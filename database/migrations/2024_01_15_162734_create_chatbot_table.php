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
        Schema::create('chatbot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_token')->unique();
            $table->uuid('user_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('title')->nullable();
            $table->json('chat_mapping')->nullable()->comment('User (message, sent_at), Assistant (message, sent_at)');
            $table->enum('status', ['Active', 'Archived', 'Blocked', 'Clear'])->default('Active');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot');
    }
};
