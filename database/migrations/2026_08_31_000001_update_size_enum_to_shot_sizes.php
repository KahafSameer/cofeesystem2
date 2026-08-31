<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update the size ENUM values on orders and carts tables from
     * Small/Medium/Large to Single Shot/Double Shot/Standard.
     */
    public function up(): void
    {
        // Update existing data first (map old values to new ones)
        DB::statement("UPDATE orders SET size = 'Single Shot' WHERE size = 'Small'");
        DB::statement("UPDATE orders SET size = 'Double Shot' WHERE size = 'Medium'");
        DB::statement("UPDATE orders SET size = 'Standard'    WHERE size = 'Large'");

        DB::statement("UPDATE carts SET size = 'Single Shot' WHERE size = 'Small'");
        DB::statement("UPDATE carts SET size = 'Double Shot' WHERE size = 'Medium'");
        DB::statement("UPDATE carts SET size = 'Standard'    WHERE size = 'Large'");

        // Alter the ENUM column on orders
        DB::statement("ALTER TABLE orders MODIFY COLUMN size ENUM('Single Shot','Double Shot','Standard') NOT NULL DEFAULT 'Standard'");

        // Alter the ENUM column on carts
        DB::statement("ALTER TABLE carts MODIFY COLUMN size ENUM('Single Shot','Double Shot','Standard') NOT NULL DEFAULT 'Standard'");
    }

    public function down(): void
    {
        // Revert data
        DB::statement("UPDATE orders SET size = 'Small'  WHERE size = 'Single Shot'");
        DB::statement("UPDATE orders SET size = 'Medium' WHERE size = 'Double Shot'");
        DB::statement("UPDATE orders SET size = 'Large'  WHERE size = 'Standard'");

        DB::statement("UPDATE carts SET size = 'Small'  WHERE size = 'Single Shot'");
        DB::statement("UPDATE carts SET size = 'Medium' WHERE size = 'Double Shot'");
        DB::statement("UPDATE carts SET size = 'Large'  WHERE size = 'Standard'");

        DB::statement("ALTER TABLE orders MODIFY COLUMN size ENUM('Small','Medium','Large') NOT NULL DEFAULT 'Medium'");
        DB::statement("ALTER TABLE carts MODIFY COLUMN size ENUM('Small','Medium','Large') NOT NULL DEFAULT 'Medium'");
    }
};
