<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('waiter_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('waiter_id');

            $table->foreign('waiter_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['waiter_id', 'branch_id']);
        });
    }
};
