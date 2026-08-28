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
        Schema::create('order_rejects', function (Blueprint $table) {
            $table->id();
            // $table->integer('user_id');
            $table->integer('product_id');
            // $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->string('order_code');
            $table->longText('reason');
            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');

            // $table->foreign('product_id')
            // ->references('id')
            // ->on('products')
            // ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_rejects');
    }
};
