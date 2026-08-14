<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'price' => fake()->numberBetween(10000, 5000000),
            'description' => fake()->paragraph(),
            'stock' => fake()->numberBetween(0, 100),
        ];
    }
}
