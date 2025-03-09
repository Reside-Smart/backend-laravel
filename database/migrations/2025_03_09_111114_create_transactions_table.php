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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('sell|rent');
            $table->decimal('amount_paid');
            $table->string('payment_status')->comment('unpaid|paid');
            $table->string('payment_method')->comment('cash|credit_card');
            $table->string('payment_date')->nullable();
            $table->string('check_in_date');
            $table->string('check_out_date')->nullable();
            $table->foreignId('listing_id')->references('id')->on('listings')->restrictOnDelete();
            $table->foreignId('buyer_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('seller_id')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
