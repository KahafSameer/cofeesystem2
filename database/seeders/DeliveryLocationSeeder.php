<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeliveryLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('delivery_fees')->insert([
            ['city' => 'Yangon', 'township' => 'Hlaing', 'fees' => '1000'],
            ['city' => 'Yangon', 'township' => 'Kamaryut', 'fees' => '1200'],
            ['city' => 'Yangon', 'township' => 'Insein', 'fees' => '1500'],
            ['city' => 'Yangon', 'township' => 'Kyimyindaing', 'fees' => '1300'],
            ['city' => 'Mandalay', 'township' => 'Chan Aye', 'fees' => '1300'],
            ['city' => 'Naypyidaw', 'township' => 'Ottarathiri', 'fee' => '2500'],


        ]);
    }
}
