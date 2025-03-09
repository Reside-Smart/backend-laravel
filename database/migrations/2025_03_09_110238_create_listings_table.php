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
        // nullable kelon 3aashen eza draft
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type')->comment('sell|rent')->nullable();
            $table->json('location')->nullable();
            $table->mediumText('address')->nullable();
            $table->decimal('price')->nullable();
            $table->integer('renting_duration')->nullable()->comment('in months');
            $table->json('images')->nullable();
            $table->json('features')->nullable();
            $table->mediumText('description')->nullable();
            $table->string('status')->nullable()->comment('draft|published');
            $table->decimal('average_reviews')->nullable();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('category_id')->references('id')->on('categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
