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
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('bukti_pengeluaran')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            // Jika ingin membatalkan perubahan tipe ENUM, kamu bisa mengubahnya kembali ke tipe data awal,
            // misalnya VARCHAR
            $table->string('bukti_pengeluaran')->change();
        });
    }
};
