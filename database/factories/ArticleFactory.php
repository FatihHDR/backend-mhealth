<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = \App\Models\Article::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);
        $content = $this->faker->paragraphs(5, true);
        $statuses = ['draft', 'published', 'archived'];

        return [
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->regexify('[0-9a-f]{6}'),
            'en_title' => $title,
            'id_title' => $this->faker->sentence(6),
            'author' => null, // assigned by seeder
            'category' => [$this->faker->word(), $this->faker->word()],
            'en_content' => $content,
            'id_content' => $this->faker->paragraphs(4, true),
            'status' => $this->faker->randomElement($statuses),
        ];
    }
}

