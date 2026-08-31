<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Restore/demo seeder for the coffeepos app.
 * Recreates baseline business data lost when the test suite was run against
 * the live DB: two branches, staff users, a coffee category/products with sizes.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Branches ----
        $mall = Branch::firstOrCreate(
            ['name' => 'mall branch'],
            ['name' => 'mall branch', 'address' => 'City Mall, Ground Floor', 'status' => 'Active']
        );
        $gt = Branch::firstOrCreate(
            ['name' => 'GT Road Branch'],
            ['name' => 'GT Road Branch', 'address' => 'GT Road, Main Bazaar', 'status' => 'Active']
        );

        // ---- Staff ----
        $password = Hash::make(env('DEFAULT_USER_PASSWORD', 'Password123'));

        User::firstOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Admin', 'password' => $password, 'provider' => 'simple',
            'role' => 'admin', 'status' => 'Active', 'branch_id' => $mall->id,
        ]);

        $waiter = User::firstOrCreate(['email' => 'waiter@gmail.com'], [
            'name' => 'Waiter', 'password' => $password, 'role' => 'waiter',
            'status' => 'Active', 'branch_id' => $gt->id,
        ]);

        // Chef for GT Road Branch
        User::firstOrCreate(['email' => 'chef@gmail.com'], [
            'name' => 'Chef GT', 'password' => $password, 'role' => 'chef',
            'status' => 'Active', 'branch_id' => $gt->id,
        ]);

        // Chef for Mall Branch
        User::firstOrCreate(['email' => 'chef1@gmail.com'], [
            'name' => 'Chef Mall', 'password' => $password, 'role' => 'chef',
            'status' => 'Active', 'branch_id' => $mall->id,
        ]);

        User::firstOrCreate(['email' => 'cashier@gmail.com'], [
            'name' => 'Cashier', 'password' => $password, 'role' => 'cashier',
            'status' => 'Active', 'branch_id' => $mall->id,
        ]);

        // Cashier for GT Road Branch so running bills requested there are visible
        // to a same-branch cashier in the Cashier Portal.
        User::firstOrCreate(['email' => 'cashier2@gmail.com'], [
            'name' => 'Cashier GT', 'password' => $password, 'role' => 'cashier',
            'status' => 'Active', 'branch_id' => $gt->id,
        ]);

        // ---- Category + products ----
        if (Category::where('name', 'Coffee')->doesntExist()) {
            $coffee = Category::create(['name' => 'Coffee']);

            $img = [
                '6a9180e8ae523WhatsApp Image 2026-08-17 at 1.29.12 PM.jpeg',
                '6a919eeeaa78bdownload.jpg',
                '6a919efd0ce0cimages (1).jpg',
                '6a91c31e0dac2images.jpg',
            ];

            $products = [
                ['name' => 'Macha', 'qty' => 100, 'image' => $img[0], 'desc' => 'Iced matcha latte', 'sizes' => ['Small' => 350, 'Medium' => 450, 'Large' => 550]],
                ['name' => 'Lava Cake', 'qty' => 50, 'image' => $img[1], 'desc' => 'Chocolate lava cake', 'sizes' => ['Small' => 400, 'Medium' => 500, 'Large' => 600]],
                ['name' => 'Cappuccino', 'qty' => 120, 'image' => $img[2], 'desc' => 'Classic cappuccino', 'sizes' => ['Small' => 300, 'Medium' => 400, 'Large' => 500]],
                ['name' => 'Americano', 'qty' => 90, 'image' => $img[3], 'desc' => 'Black coffee', 'sizes' => ['Small' => 250, 'Medium' => 350, 'Large' => 450]],
                ['name' => 'Mocha', 'qty' => 80, 'image' => $img[0], 'desc' => 'Chocolate espresso', 'sizes' => ['Small' => 380, 'Medium' => 480, 'Large' => 580]],
            ];

            foreach ($products as $p) {
                $product = Product::create([
                    'name' => $p['name'], 'qty' => $p['qty'], 'category_id' => $coffee->id,
                    'description' => $p['desc'], 'image' => $p['image'],
                ]);

                foreach ($p['sizes'] as $size => $price) {
                    ProductSize::create(['product_id' => $product->id, 'size' => $size, 'price' => $price]);
                }
            }
        }

        $this->command?->info('DemoDataSeeder complete: ' .
            Branch::count() . " branches, " . User::count() . " users, " .
            Category::count() . " categories, " . Product::count() . " products, " .
            ProductSize::count() . " product sizes.");
    }
}
