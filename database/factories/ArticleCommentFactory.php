<?php

namespace Database\Factories;

use App\Enums\ArticleCommentType;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleComment>
 */
class ArticleCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'type' => ArticleCommentType::Comment,
            'body' => fake()->sentence(12),
        ];
    }

    public function statusChange(ArticleStatus $from, ArticleStatus $to): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ArticleCommentType::StatusChange,
            'from_status' => $from,
            'to_status' => $to,
            'body' => null,
        ]);
    }
}
