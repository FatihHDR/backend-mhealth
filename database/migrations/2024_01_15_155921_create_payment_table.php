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
        Schema::create('payment', function (Blueprint $table) {
            $table->uuid('payment_id')->primary();
            $table->uuid('product_id');
            $table->uuid('user_id');
            $table->string('package_title');
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email');
            $table->text('address');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('payment_link')->nullable();
            $table->decimal('must_be_paid', 15, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->enum('status', ['Pending', 'Processing', 'Paid', 'Failed', 'Cancelled'])->default('Pending');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
