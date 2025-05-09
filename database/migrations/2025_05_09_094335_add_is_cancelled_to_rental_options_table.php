<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('rental_options', function (Blueprint $table) {
            $table->tinyInteger('is_cancelled')->default(1)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('rental_options', function (Blueprint $table) {
            $table->dropColumn('is_cancelled');
        });
    }
};
