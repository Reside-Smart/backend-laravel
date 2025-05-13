<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_discount_id')->nullable()->after('listing_id');

            // If you have a 'listing_discounts' table and want to enforce referential integrity:
            // $table->foreign('listing_discount_id')->references('id')->on('listing_discounts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // If you added a foreign key constraint, drop it first:
            // $table->dropForeign(['listing_discount_id']);
            $table->dropColumn('listing_discount_id');
        });
    }
};
