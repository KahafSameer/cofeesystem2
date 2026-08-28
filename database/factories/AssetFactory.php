<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'asset_category_id' => AssetCategory::inRandomOrder()->first()->id,
            'assigned_user_id' => User::inRandomOrder()->first()?->id,
            'purchase_date' => fake()->date(),
            'purchase_value' => fake()->randomFloat(2, 100, 5000),
            'depreciation_rate' => fake()->randomFloat(2, 0, 10),
            'status' => fake()->randomElement(['in_use', 'maintenance', 'disposed', 'missing']),
            'unit' => fake()->randomElement(['cashier', 'chef', 'admin']),
            'warranty_expiry_date' => fake()->optional()->date(),
            'serial_number' => fake()->uuid(),
            'notes' => fake()->sentence(),
        ];
    }
}
