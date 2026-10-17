<?php

namespace Database\Factories;

use App\Enums\PublicDocumentCategory;
use App\Models\PublicDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PublicDocument>
 */
class PublicDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(PublicDocumentCategory::cases()),
            'year' => (int) fake()->numberBetween(2022, (int) now()->format('Y')),
            'file_path' => 'ppid/'.now()->format('Y/m').'/'.Str::uuid().'.pdf',
            'file_size' => fake()->numberBetween(50_000, 5_000_000),
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
