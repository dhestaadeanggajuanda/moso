<?php

namespace Database\Factories;

use App\Models\SsoIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SsoIdentity>
 */
class SsoIdentityFactory extends Factory
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
            'provider' => (string) config('sso.provider'),
            'subject' => fake()->uuid(),
        ];
    }
}
