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
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('transactions')->default(true);
            $table->boolean('new_listings')->default(true);
            $table->boolean('messages')->default(true);
            $table->boolean('discounts')->default(true);
            $table->boolean('reviews')->default(true);
            $table->timestamps();
            //         'transactions': transactions,
            //   'new_listings': newListings,
            //   'messages': messages,
            //   'discounts': discounts,
            //   'reviews': reviews,
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
