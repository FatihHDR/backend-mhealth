<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = \App\Models\Event::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);
        $start = $this->faker->dateTimeBetween('-1 months', '+2 months');
        $end = (clone $start)->modify('+' . $this->faker->numberBetween(1, 5) . ' days');
        $statuses = ['draft', 'published', 'archived'];

        return [
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . $this->faker->regexify('[0-9a-f]{4}'),
            'en_title' => $title,
            'id_title' => $this->faker->sentence(6),
            'en_description' => $this->faker->paragraphs(3, true),
            'id_description' => $this->faker->paragraphs(2, true),
            'highlight_image' => 'https://placehold.co/800x600.png',
            'reference_image' => null,
            'organized_image' => 'https://placehold.co/400x200.png',
            'organized_by' => $this->faker->company(),
            'start_date' => $start,
            'end_date' => $end,
            'location_name' => $this->faker->city() . ', ' . $this->faker->country(),
            'location_map' => null,
            'status' => $this->faker->randomElement($statuses),
        ];
    }
}
