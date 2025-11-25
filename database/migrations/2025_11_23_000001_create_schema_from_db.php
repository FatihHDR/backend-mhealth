<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // about_us (bigint id)
        Schema::create('about_us', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_about_content');
            $table->text('id_about_content');
            $table->text('en_brand_tagline');
            $table->text('id_brand_tagline');
        });

        // author (used by article)
        Schema::create('author', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('name');
            $table->text('jobdesc');
            $table->text('slug')->unique();
        });

        // vendor
        Schema::create('vendor', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('name');
            $table->text('en_description');
            $table->text('id_description');
            $table->text('category');
            $table->text('location_map')->nullable();
            $table->jsonb('specialist')->nullable();
            $table->text('logo')->nullable();
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
        });

        // hotel
        Schema::create('hotel', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('name');
            $table->text('en_description');
            $table->text('id_description');
            $table->text('location_map')->nullable();
            $table->text('logo')->nullable();
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
        });

        // medical_equipment
        Schema::create('medical_equipment', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_description');
            $table->text('id_description');
            $table->string('spesific_gender')->nullable();
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
            $table->text('real_price');
            $table->text('discount_price')->nullable();
            $table->string('status');
        });

        // medical
        Schema::create('medical', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_tagline');
            $table->text('id_tagline');
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
            $table->integer('duration_by_day');
            $table->integer('duration_by_night')->nullable();
            $table->string('spesific_gender');
            $table->text('en_medical_package_content');
            $table->text('id_medical_package_content');
            $table->jsonb('included')->nullable();
            $table->uuid('vendor_id');
            $table->text('real_price');
            $table->text('discount_price')->nullable();
            $table->string('status')->default('draft');

            $table->foreign('vendor_id')->references('id')->on('vendor')->onDelete('cascade');
        });

        // packages
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_tagline');
            $table->text('id_tagline');
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
            $table->integer('duration_by_day');
            $table->integer('duration_by_night')->nullable();
            $table->string('spesific_gender');
            $table->text('en_medical_package_content');
            $table->text('id_medical_package_content');
            $table->text('en_wellness_package_content');
            $table->text('id_wellness_package_content');
            $table->jsonb('included')->nullable();
            $table->uuid('vendor_id');
            $table->uuid('hotel_id');
            $table->text('real_price');
            $table->text('discount_price')->nullable();
            $table->string('status')->default('draft');

            $table->foreign('vendor_id')->references('id')->on('vendor')->onDelete('cascade');
            $table->foreign('hotel_id')->references('id')->on('hotel')->onDelete('cascade');
        });

        // wellness
        Schema::create('wellness', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_tagline');
            $table->text('id_tagline');
            $table->text('highlight_image');
            $table->jsonb('reference_image')->nullable();
            $table->integer('duration_by_day');
            $table->integer('duration_by_night')->nullable();
            $table->string('spesific_gender');
            $table->text('en_wellness_package_content');
            $table->text('id_wellness_package_content');
            $table->jsonb('included')->nullable();
            $table->uuid('hotel_id');
            $table->text('real_price');
            $table->text('discount_price')->nullable();
            $table->string('status')->default('draft');

            $table->foreign('hotel_id')->references('id')->on('hotel')->onDelete('cascade');
        });

        // article_category
        Schema::create('article_category', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('en_category');
            $table->text('id_category');
            $table->text('en_description')->nullable();
            $table->text('id_description')->nullable();
        });

        // article
        Schema::create('article', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug')->unique();
            $table->text('en_title');
            $table->text('id_title');
            $table->uuid('author');
            $table->jsonb('category')->nullable();
            $table->text('en_content');
            $table->text('id_content');
            $table->string('status');

            $table->foreign('author')->references('id')->on('author')->onDelete('cascade');
        });

        // chat_activity
        Schema::create('chat_activity', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('title');
            $table->jsonb('chat_activity_data');
            $table->uuid('public_id')->nullable();
            $table->uuid('user_id')->nullable();
        });

        // consult_schedule
        Schema::create('consult_schedule', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('fullname');
            $table->text('complaint');
            $table->date('date_of_birth');
            $table->integer('height');
            $table->integer('weight');
            $table->string('gender');
            $table->jsonb('location');
            $table->timestampTz('scheduled_date');
            $table->timeTz('scheduled_time');
            $table->string('payment_status');
        });

        // events
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('slug');
            $table->text('en_title');
            $table->text('id_title');
            $table->text('en_description');
            $table->text('id_description');
            $table->text('highlight_image');
            $table->text('reference_image')->nullable();
            $table->text('organized_image');
            $table->text('organized_by');
            $table->timestampTz('start_date');
            $table->timestampTz('end_date');
            $table->text('location_name');
            $table->text('location_map')->nullable();
            $table->string('status');
        });

        // payment_records
        Schema::create('payment_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('transaction_id');
            $table->text('fullname');
            $table->text('email');
            $table->text('phone');
            $table->text('address');
            $table->string('payment_status');
            $table->uuid('packages_id')->nullable();
            $table->uuid('medical_id')->nullable();
            $table->uuid('wellness_id')->nullable();
            $table->uuid('consultation_id')->nullable();
            $table->uuid('medical_equipment_id')->nullable();

            $table->foreign('packages_id')->references('id')->on('packages')->onDelete('set null');
            $table->foreign('medical_id')->references('id')->on('medical')->onDelete('set null');
            $table->foreign('wellness_id')->references('id')->on('wellness')->onDelete('set null');
            $table->foreign('consultation_id')->references('id')->on('consult_schedule')->onDelete('set null');
            $table->foreign('medical_equipment_id')->references('id')->on('medical_equipment')->onDelete('set null');
        });

        // accounts (references users)
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable()->default(DB::raw('now()'));
            $table->text('email');
            $table->text('fullname')->nullable();
            $table->text('phone')->nullable();
            $table->string('gender')->nullable();
            $table->jsonb('domicile')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->text('avatar_url')->nullable();
            $table->date('birthdate')->nullable();

            // If you have a users table with matching UUIDs enable FK, otherwise leave it commented.
            // $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('events');
        Schema::dropIfExists('consult_schedule');
        Schema::dropIfExists('chat_activity');
        Schema::dropIfExists('article');
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('wellness');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('medical');
        Schema::dropIfExists('medical_equipment');
        Schema::dropIfExists('hotel');
        Schema::dropIfExists('vendor');
        Schema::dropIfExists('author');
        Schema::dropIfExists('about_us');
    }
};
