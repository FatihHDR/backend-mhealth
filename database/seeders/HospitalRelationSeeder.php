<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HospitalRelationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $hospitals = [
            [
                'id' => Str::uuid(),
                'name' => 'Husada Utama',
                'description' => 'Rumah Sakit Husada Utama merupakan rumah sakit terkemuka yang menyediakan layanan kesehatan berkualitas tinggi dengan fasilitas modern dan tim medis profesional.',
                'hospital_map' => 'https://maps.google.com/?q=RS+Husada+Utama',
                'specialist' => json_encode([
                    'Jantung & Pembuluh Darah',
                    'Fertilitas & Reproduksi',
                    'Bedah Umum'
                ]),
                'logo' => null,
                'highlight_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'RSUD Soewandi',
                'description' => 'RSUD Soewandhie adalah rumah sakit umum daerah yang melayani masyarakat dengan berbagai layanan kesehatan komprehensif termasuk medical check-up dan bedah plastik.',
                'hospital_map' => 'https://maps.google.com/?q=RSUD+Soewandhie',
                'specialist' => json_encode([
                    'Ortopedi',
                    'Psikologi & Psikiatri',
                    'Kardiologi',
                    'Bedah Plastik'
                ]),
                'logo' => null,
                'highlight_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Rumah Sakit',
                'description' => 'Rumah Sakit Umum yang menyediakan berbagai layanan kesehatan dan medical check-up dengan harga terjangkau.',
                'hospital_map' => 'https://maps.google.com/?q=Rumah+Sakit',
                'specialist' => json_encode([
                    'Medical Check-Up',
                    'Spa & Wellness',
                    'Bedah Plastik',
                    'Onkologi'
                ]),
                'logo' => null,
                'highlight_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'RS Ngoerah Sun Bali',
                'description' => 'RS Ngoerah Sun Bali adalah rumah sakit premium di Bali yang mengombinasikan layanan kesehatan dengan pengalaman wellness di destinasi wisata.',
                'hospital_map' => 'https://maps.google.com/?q=RS+Ngoerah+Sun+Bali',
                'specialist' => json_encode([
                    'Medical Check-Up Premium',
                    'Wellness & Preventive Health',
                    'Kardiologi',
                    'Ginekologi'
                ]),
                'logo' => null,
                'highlight_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'RS Bethesda Yogyakarta',
                'description' => 'RS Bethesda Yogyakarta menawarkan layanan medical check-up dengan pendekatan holistik yang mengintegrasikan pengobatan modern dan tradisional Jawa.',
                'hospital_map' => 'https://maps.google.com/?q=RS+Bethesda+Yogyakarta',
                'specialist' => json_encode([
                    'Medical Check-Up',
                    'Endokrinologi',
                    'Nefrologi',
                    'Onkologi'
                ]),
                'logo' => null,
                'highlight_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('hospital_relation')->insert($hospitals);
    }
}
