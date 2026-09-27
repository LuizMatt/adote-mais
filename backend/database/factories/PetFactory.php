<?php

namespace Database\Factories;

use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(['dog', 'cat', 'other']),
            'size' => fake()->randomElement(['small', 'medium', 'large']),
            'approximate_age' => '1 year',
            'gender' => fake()->randomElement(['male', 'female']),
            'photo_path' => null,
            'description' => fake()->sentence(),
            'vaccines' => [
                ['name' => 'Rabies', 'applied_at' => '2026-01-10'],
            ],
            'status' => 'available',
            'rescue_date' => now()->subMonths(1)->toDateString(),
            'adopter_name' => null,
            'adoption_date' => null,
        ];
    }

    public function adopted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'adopted',
            'adopter_name' => fake()->name(),
            'adoption_date' => now()->toDateString(),
        ]);
    }
}
