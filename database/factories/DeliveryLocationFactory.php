<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryLocation>
 */
class DeliveryLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
            'city' => $this->faker->randomElement(['Yangon', 'Mandalay', 'Naypyidaw']),
            'township' => $this->faker->streetName(),
            'fee' => $this->faker->numberBetween(1000, 3000),
        ];
    }
}
