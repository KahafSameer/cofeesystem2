<?php
namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// use Database\Seeders\OrderSeeder;
use Database\Seeders\DeliveryLocationSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ],
            [
                'name'     => 'Admin',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'Password123')),
                'provider' => 'simple',
                'role'     => 'admin',
                'status'   => 'Active',
            ]
        );

        $this->call([
            // Delivery
            DeliveryLocationSeeder::class,

        ]);

    }
}
