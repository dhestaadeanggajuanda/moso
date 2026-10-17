<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => rtrim(fake()->sentence(6), '.'),
            'excerpt' => fake()->sentence(18),
            'body' => collect(range(1, 4))
                ->map(fn () => '<p>'.fake()->paragraph().'</p>')
                ->implode(''),
            'status' => ArticleStatus::Draft,
        ];
    }

    public function status(ArticleStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'published_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ]);
    }
}
