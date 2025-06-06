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
        Schema::create('listing_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('percentage');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('status')->default('active')->comment('inactive|active');
            $table->foreignId('listing_id')->references('id')->on('listings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_discounts');
    }
};
