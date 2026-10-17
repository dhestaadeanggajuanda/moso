<?php

namespace Database\Factories;

use App\Models\Agenda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agenda>
 */
class AgendaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', '+1 month');

        return [
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'location' => fake()->streetAddress(),
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+2 hours'),
            'organizer' => fake()->company(),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
