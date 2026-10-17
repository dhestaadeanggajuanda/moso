<?php

namespace Database\Factories;

use App\Enums\SakipDocumentType;
use App\Models\ESakip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ESakip>
 */
class ESakipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(SakipDocumentType::cases());
        $year = (int) fake()->numberBetween(2022, (int) now()->format('Y'));

        return [
            'year' => $year,
            'document_type' => $type,
            'document_title' => "{$type->label()} Tahun {$year}",
            'file_path_s3' => 'sakip/'.now()->format('Y/m').'/'.Str::uuid().'.pdf',
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
