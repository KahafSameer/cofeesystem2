<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional delivery destination for a cashier ticket (used to compute the
     * delivery fee). Additive; idempotent on environments that already have it.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cashier_drafts') || Schema::hasColumn('cashier_drafts', 'delivery_location_id')) {
            return;
        }

        Schema::table('cashier_drafts', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_location_id')->nullable()->after('order_type');

            $table->foreign('delivery_location_id')
                ->references('id')
                ->on('delivery_fees')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cashier_drafts') || ! Schema::hasColumn('cashier_drafts', 'delivery_location_id')) {
            return;
        }

        Schema::table('cashier_drafts', function (Blueprint $table) {
            $table->dropForeign(['delivery_location_id']);
            $table->dropColumn('delivery_location_id');
        });
    }
};