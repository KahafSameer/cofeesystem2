<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update the size ENUM values on orders and carts tables from
     * Small/Medium/Large  →  Single Shot/Double Shot/Standard.
     *
     * Strategy (3-step to avoid MySQL truncation warnings):
     *   1. Widen to VARCHAR so all old values remain valid.
     *   2. UPDATE existing rows to the new names.
     *   3. Re-apply the ENUM now that every row holds a valid new value.
     */
    public function up(): void
    {
        // --- Step 1: widen to VARCHAR (accepts both old and new names) ---
        DB::statement("ALTER TABLE orders MODIFY COLUMN size VARCHAR(50) NOT NULL DEFAULT 'Standard'");
        DB::statement("ALTER TABLE carts  MODIFY COLUMN size VARCHAR(50) NOT NULL DEFAULT 'Standard'");

        // --- Step 2: migrate existing data ---
        DB::statement("UPDATE orders SET size = 'Single Shot' WHERE size = 'Small'");
        DB::statement("UPDATE orders SET size = 'Double Shot' WHERE size = 'Medium'");
        DB::statement("UPDATE orders SET size = 'Standard'    WHERE size = 'Large'");

        DB::statement("UPDATE carts SET size = 'Single Shot' WHERE size = 'Small'");
        DB::statement("UPDATE carts SET size = 'Double Shot' WHERE size = 'Medium'");
        DB::statement("UPDATE carts SET size = 'Standard'    WHERE size = 'Large'");

        // --- Step 3: lock back to ENUM (all rows now hold valid values) ---
        DB::statement("ALTER TABLE orders MODIFY COLUMN size ENUM('Single Shot','Double Shot','Standard') NOT NULL DEFAULT 'Standard'");
        DB::statement("ALTER TABLE carts  MODIFY COLUMN size ENUM('Single Shot','Double Shot','Standard') NOT NULL DEFAULT 'Standard'");
    }

    public function down(): void
    {
        // Widen first so old values are accepted again.
        DB::statement("ALTER TABLE orders MODIFY COLUMN size VARCHAR(50) NOT NULL DEFAULT 'Medium'");
        DB::statement("ALTER TABLE carts  MODIFY COLUMN size VARCHAR(50) NOT NULL DEFAULT 'Medium'");

        DB::statement("UPDATE orders SET size = 'Small'  WHERE size = 'Single Shot'");
        DB::statement("UPDATE orders SET size = 'Medium' WHERE size = 'Double Shot'");
        DB::statement("UPDATE orders SET size = 'Large'  WHERE size = 'Standard'");

        DB::statement("UPDATE carts SET size = 'Small'  WHERE size = 'Single Shot'");
        DB::statement("UPDATE carts SET size = 'Medium' WHERE size = 'Double Shot'");
        DB::statement("UPDATE carts SET size = 'Large'  WHERE size = 'Standard'");

        DB::statement("ALTER TABLE orders MODIFY COLUMN size ENUM('Small','Medium','Large') NOT NULL DEFAULT 'Medium'");
        DB::statement("ALTER TABLE carts  MODIFY COLUMN size ENUM('Small','Medium','Large') NOT NULL DEFAULT 'Medium'");
    }
};
