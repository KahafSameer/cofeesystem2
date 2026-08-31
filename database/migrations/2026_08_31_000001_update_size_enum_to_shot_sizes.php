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
        // First change the ENUM definitions so the new values are valid.
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN size
            ENUM('Single Shot','Double Shot','Standard')
            NOT NULL DEFAULT 'Standard'
        ");

        DB::statement("
            ALTER TABLE carts
            MODIFY COLUMN size
            ENUM('Single Shot','Double Shot','Standard')
            NOT NULL DEFAULT 'Standard'
        ");

        // Now convert existing data.
        DB::statement("
            UPDATE orders
            SET size = CASE
                WHEN size = 'Small' THEN 'Single Shot'
                WHEN size = 'Medium' THEN 'Double Shot'
                WHEN size = 'Large' THEN 'Standard'
                ELSE size
            END
        ");

        DB::statement("
            UPDATE carts
            SET size = CASE
                WHEN size = 'Small' THEN 'Single Shot'
                WHEN size = 'Medium' THEN 'Double Shot'
                WHEN size = 'Large' THEN 'Standard'
                ELSE size
            END
        ");
    }

    public function down(): void
    {
        // First convert new values back to old values.
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN size
            ENUM('Small','Medium','Large')
            NOT NULL DEFAULT 'Medium'
        ");

        DB::statement("
            ALTER TABLE carts
            MODIFY COLUMN size
            ENUM('Small','Medium','Large')
            NOT NULL DEFAULT 'Medium'
        ");
    }
};