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
        Schema::table('harga_air_permeters', function (Blueprint $table) {
            $table->integer('beban_meteran')->after('harga')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harga_air_permeters', function (Blueprint $table) {
            $table->dropColumn('beban_meteran');
        });
    }
};
