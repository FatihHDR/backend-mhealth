<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Article;
use App\Models\Author;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authorsCount = Author::count();
        if ($authorsCount < 5) {
            for ($i = 0; $i < 5; $i++) {
                $name = 'Author ' . Str::random(6);
                Author::create([
                    'name' => $name,
                    'jobdesc' => 'Contributor',
                    'slug' => Str::slug($name),
                ]);
            }
        }

        $authors = Author::all();

        Article::factory()->count(30)->make()->each(function ($article) use ($authors) {
            $article->author = $authors->random()->id;
            $article->slug = $article->slug . '-' . Str::random(4);
            $article->save();
        });

        $this->command->info('ArticleSeeder: created 30 dummy articles');
    }
}
