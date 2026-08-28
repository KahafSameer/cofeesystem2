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
        // Schema::dropIfExists('orders'); // Drops the table only if it exists
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // $table->integer('product_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->char('status', 1);
            $table->string('order_code');
            $table->integer('quantity');
            $table->decimal('totalprice', 10, 2);
            $table->timestamps();
            $table->string('payment_method')->nullable();
            $table->string('order_type')->nullable();
            $table->enum('size', ['Small', 'Medium', 'Large'])->default('Medium');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('delivery_location_id')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
