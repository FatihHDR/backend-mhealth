<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $users = [
            [
                'id' => Str::uuid(),
                'email' => 'admin@mhealth.com',
                'phone_number' => '081234567890',
                'profile_picture' => null,
                'full_name' => 'Super Admin',
                'gender' => 'Male',
                'domicile' => json_encode([
                    'province' => 'DKI Jakarta',
                    'city' => 'Jakarta Selatan',
                    'district' => 'Kebayoran Baru',
                    'sub_district' => 'Senayan',
                    'address' => 'Jl. Asia Afrika No. 8',
                    'postal_code' => '10270',
                ]),
                'weight' => 70.5,
                'height' => 175.0,
                'password' => Hash::make('password123'),
                'is_verified' => true,
                'oauth' => null,
                'sign_in_device' => json_encode([
                    'device_type' => 'web',
                    'browser' => 'Chrome',
                    'os' => 'Windows 10',
                ]),
                'last_signed_in' => now(),
                'is_active' => true,
                'role' => 'Super Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'email' => 'admin.hospital@mhealth.com',
                'phone_number' => '081234567891',
                'profile_picture' => null,
                'full_name' => 'Hospital Admin',
                'gender' => 'Female',
                'domicile' => json_encode([
                    'province' => 'Jawa Timur',
                    'city' => 'Surabaya',
                    'district' => 'Gubeng',
                    'sub_district' => 'Airlangga',
                    'address' => 'Jl. Airlangga No. 4-6',
                    'postal_code' => '60286',
                ]),
                'weight' => 55.0,
                'height' => 165.0,
                'password' => Hash::make('password123'),
                'is_verified' => true,
                'oauth' => null,
                'sign_in_device' => json_encode([
                    'device_type' => 'web',
                    'browser' => 'Firefox',
                    'os' => 'Windows 11',
                ]),
                'last_signed_in' => now(),
                'is_active' => true,
                'role' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'email' => 'user@example.com',
                'phone_number' => '081234567892',
                'profile_picture' => null,
                'full_name' => 'John Doe',
                'gender' => 'Male',
                'domicile' => json_encode([
                    'province' => 'Bali',
                    'city' => 'Denpasar',
                    'district' => 'Denpasar Selatan',
                    'sub_district' => 'Sanur',
                    'address' => 'Jl. Danau Tamblingan No. 100',
                    'postal_code' => '80228',
                ]),
                'weight' => 75.0,
                'height' => 178.0,
                'password' => Hash::make('password123'),
                'is_verified' => true,
                'oauth' => null,
                'sign_in_device' => json_encode([
                    'device_type' => 'mobile',
                    'browser' => 'Chrome Mobile',
                    'os' => 'Android 14',
                ]),
                'last_signed_in' => now(),
                'is_active' => true,
                'role' => 'User',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
