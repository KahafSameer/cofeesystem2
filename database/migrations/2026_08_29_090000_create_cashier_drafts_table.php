<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cashier POS ticket headers. One row = one independent ticket (draft).
     * Cart rows live in the existing `carts` table, keyed by `orderCode`,
     * so each ticket keeps its own isolated cart.
     *
     * Guarded with create-if-not-exists so it is safe on the live database
     * where the table was already created.
     */
    public function up(): void
    {
        if (Schema::hasTable('cashier_drafts')) {
            return;
        }

        Schema::create('cashier_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('order_code')->unique();
            $table->string('label')->nullable();
            $table->string('status', 255)->default('active'); // active | suspended | paid | discarded
            $table->tinyInteger('order_type')->unsigned()->default(1); // 1 eat_in | 2 take_away | 3 delivery
            $table->timestamps();

            $table->index(['cashier_id', 'branch_id']);

            $table->foreign('cashier_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_drafts');
    }
};