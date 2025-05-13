<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the 'listing_discount_id' column if it exists
            $table->dropColumn('listing_discount_id');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {

            $table->unsignedBigInteger('listing_discount_id')->nullable();

            // If there was a foreign key constraint, re-add it as well
            // $table->foreign('listing_discount_id')->references('id')->on('listing_discounts')->onDelete('set null');
        });
    }
};
