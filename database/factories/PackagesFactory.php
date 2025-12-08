<?php

namespace Database\Factories;

use App\Helpers\SlugHelper;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Packages>
 */
class PackagesFactory extends Factory
{
    protected $model = \App\Models\Packages::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        $genders = ['both', 'male', 'female'];
        $statuses = ['draft', 'published', 'archived'];

        return [
            'slug' => SlugHelper::generate($title),
            'en_title' => $title,
            'id_title' => $this->faker->sentence(4),
            'en_tagline' => $this->faker->sentence(8),
            'id_tagline' => $this->faker->sentence(8),
            'en_detail' => $this->faker->paragraphs(2, true),
            'id_detail' => $this->faker->paragraphs(2, true),
            'highlight_image' => $this->faker->imageUrl(800, 600, 'nature'),
            'reference_image' => [
                $this->faker->imageUrl(400, 300, 'nature'),
                $this->faker->imageUrl(400, 300, 'nature'),
            ],
            'duration_by_day' => $this->faker->numberBetween(1, 14),
            'duration_by_night' => $this->faker->numberBetween(0, 13),
            'spesific_gender' => $this->faker->randomElement($genders),
            'en_medical_package_content' => $this->faker->paragraphs(3, true),
            'id_medical_package_content' => $this->faker->paragraphs(3, true),
            'en_wellness_package_content' => $this->faker->paragraphs(3, true),
            'id_wellness_package_content' => $this->faker->paragraphs(3, true),
            'included' => $this->faker->randomElements(['spa', 'yoga', 'meditation', 'massage', 'sauna', 'gym', 'pool', 'breakfast', 'lunch', 'dinner'], 4),
            'vendor_id' => null, // assigned by seeder
            'hotel_id' => null, // assigned by seeder
            'real_price' => (string) $this->faker->numberBetween(1000000, 10000000),
            'discount_price' => (string) $this->faker->numberBetween(500000, 9000000),
            'status' => $this->faker->randomElement($statuses),
        ];
    }

    /**
     * Set the package status to published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Set the package status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Set the package for male only.
     */
    public function maleOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'spesific_gender' => 'male',
        ]);
    }

    /**
     * Set the package for female only.
     */
    public function femaleOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'spesific_gender' => 'female',
        ]);
    }
}
