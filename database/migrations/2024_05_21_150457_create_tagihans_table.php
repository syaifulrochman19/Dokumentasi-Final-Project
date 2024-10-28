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
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')->constrained('penduduks')->cascadeOnDelete();
            $table->date('bulan_tagihan');
            $table->integer('tahun_tagihan');
            $table->integer('meteran_awal');
            $table->integer('meteran_akhir');
            $table->integer('tagihan_meteran');
            $table->integer('total_tagihan');
            $table->enum('status_tagihan', ['Belum Lunas', 'Lunas']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
