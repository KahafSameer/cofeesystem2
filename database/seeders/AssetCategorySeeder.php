<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AssetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = ['Furniture', 'Electronics', 'Kitchen Equipment', 'POS Device', 'Others'];

        foreach ($categories as $category) {
            AssetCategory::create(['name' => $category]);
        }
    }
}
