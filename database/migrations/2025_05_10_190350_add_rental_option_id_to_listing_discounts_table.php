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
        Schema::table('listing_discounts', function (Blueprint $table) {
            $table->foreignId('rental_option_id')
                ->nullable()
                ->constrained('rental_options')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_discounts', function (Blueprint $table) {
            $table->dropForeign(['rental_option_id']);
            $table->dropColumn('rental_option_id');
        });
    }
};
