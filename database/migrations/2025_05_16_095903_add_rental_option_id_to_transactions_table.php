<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_option_id')->nullable()->after('discount_id');

            $table->foreign('rental_option_id')
                ->references('id')
                ->on('rental_options')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['rental_option_id']);
            $table->dropColumn('rental_option_id');
        });
    }
};
