<?php

namespace Database\Factories;

use App\Enums\NewsStatus;
use App\Models\User;
use App\Modules\News\Models\NewsPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsPostFactory extends Factory
{
    protected $model = NewsPost::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'status' => NewsStatus::Draft,
            'is_featured' => false,
            'reading_time' => 2,
        ];
    }

    public function translated(?string $titleEs = null, ?string $titleEn = null): static
    {
        return $this->afterCreating(function (NewsPost $post) use ($titleEs, $titleEn) {
            foreach (['es' => $titleEs ?? 'Noticia de prueba', 'en' => $titleEn ?? 'Test news post'] as $locale => $title) {
                $post->translations()->create([
                    'locale' => $locale,
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.$post->id,
                    'excerpt' => fake()->sentence(),
                    'content' => '<p>'.fake()->paragraph().'</p>',
                ]);
            }
        });
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => NewsStatus::Published, 'published_at' => now()->subDay()]);
    }
}
