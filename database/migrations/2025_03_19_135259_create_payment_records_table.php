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
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            // $table->integer('product_id')->unsigned(); // Foreign key reference to products table
            // $table->integer('user_id')->unsigned();    // Foreign key reference to users table

            $table->unsignedBigInteger('user_id');
            $table->string('status', 1);               // Char for single-character status
            $table->string('order_code');              // Order identifier
            $table->decimal('net_amount', 10, 2);      // Total after applying discounts
            $table->decimal('paid_amount', 10, 2);     // Amount actually paid
            $table->decimal('change_amount', 10, 2);   // Change given (if applicable)
            $table->string('payment_method');          // Payment method (e.g., Cash, Card)
            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
