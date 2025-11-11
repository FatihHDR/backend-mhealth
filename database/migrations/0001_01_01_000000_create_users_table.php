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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('full_name');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->json('domicile')->nullable()->comment('Province, City, District, SubDistrict, Address, Postal Code');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->string('password');
            $table->boolean('is_verified')->default(false);
            $table->json('oauth')->nullable();
            $table->json('sign_in_device')->nullable();
            $table->timestamp('last_signed_in')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('role', ['Super Admin', 'Admin', 'User'])->default('User');
            $table->rememberToken();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
