<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // create 20 sample events
        Event::factory()->count(20)->create();

        $this->command->info('EventSeeder: created 20 dummy events');
    }
}
