<?php

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location' => MenuLocation::Header,
            'label' => ucfirst(fake()->word()),
            'url' => '/'.fake()->slug(2),
            'open_in_new_tab' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function childOf(Menu $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'location' => $parent->location,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
