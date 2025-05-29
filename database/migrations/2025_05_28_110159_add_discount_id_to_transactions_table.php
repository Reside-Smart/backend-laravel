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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_id')->nullable()->after('listing_id');

            // If you have a 'listing_discounts' table and want to enforce referential integrity:
            $table->foreign('discount_id')->references('id')->on('listing_discounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // If you added a foreign key constraint, drop it first:
            $table->dropForeign(['discount_id']);
            $table->dropColumn('discount_id');
        });
    }
};
