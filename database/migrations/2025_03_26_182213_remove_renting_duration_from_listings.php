<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('renting_duration');
        });
    }

    public function down()
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('renting_duration')->nullable(); // Add back if needed
        });
    }
};
