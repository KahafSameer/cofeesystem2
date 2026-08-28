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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('asset_category_id');
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_value', 10, 2);
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->string('status'); // in_use, maintenance, disposed, missing
            $table->string('unit');//kitchen, cashier, admin
            $table->date('warranty_expiry_date')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('asset_category_id')
                  ->references('id')
                  ->on('asset_categories')
                  ->onDelete('cascade');

            $table->foreign('assigned_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
