<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // existing seeders
            UserSeeder::class,
            HospitalRelationSeeder::class,
            MedicalPackageSeeder::class,
            LatestPackageSeeder::class,
            HealingMeditationSeeder::class,
            HealthStepProgramSeeder::class,

            // CSV-based importers (one seeder per CSV in `csv-files/`)
            AboutUsSeeder::class,
            HotelListSeeder::class,
            MedicalSeeder::class,
            MedicalEquipmentListSeeder::class,
            PackageListSeeder::class,
            VendorListSeeder::class,
            WellnessListSeeder::class,
            WellnessPackagePricingSeeder::class,
        ]);

        $this->command->info('All seeders completed successfully! 🎉');
    }
}
