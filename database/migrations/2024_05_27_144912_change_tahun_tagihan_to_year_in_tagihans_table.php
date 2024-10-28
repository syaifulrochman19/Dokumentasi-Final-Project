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
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropColumn('tahun_tagihan'); // Hapus kolom yang lama terlebih dahulu
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->year('tahun_tagihan'); // Tambahkan kolom baru dengan tipe year
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropColumn('tahun_tagihan'); // Hapus kolom dengan tipe year

            $table->date('tahun_tagihan'); // Tambahkan kembali kolom dengan tipe date (atau tipe yang sebelumnya)
        });

    }
};
