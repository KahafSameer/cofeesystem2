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
        Schema::create('customer_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status', 20)->default('open'); // open | bill_requested | closed
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('bill_requested_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('customer_sessions');
    }
};
