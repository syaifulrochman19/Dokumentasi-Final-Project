<?php

namespace App\Models;

use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagihan_id',
        'kategori_id',
        'tanggal_pembayaran',
        'jumlah_pembayaran',
        'keterangan',
        'bukti_pembayaran',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function getJenisTransaksiAttribute()
    {
    return $this->kategori->pemasukan ? 'Pemasukan' : 'Pengeluaran';
    }

    public function scopeIncomes($query)
    {
        return $query->whereHas('kategori', function ($query){
            $query->where('pemasukan', true);
        });
    }

    protected static function boot()
    {
        parent::boot();
        //Metode deleting di atas adalah event model. Ini disebut "deleting"
        // karena dipanggil sebelum model dihapus dari database.
        //Ketika transaksi dihapus, event ini dipicu.
        static::deleting(function ($transaksi) {
            // Ambil tagihan terkait
            $tagihan = $transaksi->tagihan;

            // Ubah status tagihan menjadi 'Belum Lunas'
            $tagihan->update(['status_tagihan' => 'Belum Lunas']);
        });
    }

}
