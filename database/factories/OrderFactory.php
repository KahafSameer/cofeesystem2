<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            // 'product_id' => Product::inRandomOrder()->first()->id ?? 1,
            // 'user_id' => User::inRandomOrder()->first()->id ?? 1,
            // 'status' => fake()->randomElement(['0','1','2']),
            // 'order_code' => 'ORD-' . strtoupper(Str::random(6)),
            // 'quantity' => fake()->numberBetween(1,5),
            // 'totalprice' => fake()->randomFloat(2,10,500),
            // 'payment_method' => fake()->randomElement(['cash','card','mobile']),
            // 'order_type' => fake()->randomElement(['eat-in','takeaway','delivery']),
            // 'size' => fake()->randomElement(['Small','Medium','Large']),
            // 'notes' => fake()->optional()->sentence(),
        ];
    }
}
