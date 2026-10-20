<?php

namespace Database\Factories;

use App\Models\ArticleAiDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleAiDraft>
 */
class ArticleAiDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'remote_id' => fake()->uuid(),
        ];
    }
}
