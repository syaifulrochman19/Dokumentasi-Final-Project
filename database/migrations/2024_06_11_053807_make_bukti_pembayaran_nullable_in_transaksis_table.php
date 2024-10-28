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
        Schema::table('transaksis', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Jika ingin membatalkan perubahan nullable, kamu bisa menghapus nullable()
            // Namun, jika kamu tidak yakin apakah data yang ada sesuai dengan skema baru,
            // sebaiknya hindari menghapus nullable() dalam migrasi turunannya.
            $table->string('bukti_pembayaran')->change();
        });
    }
};
