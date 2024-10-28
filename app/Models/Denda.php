<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Denda extends Model
{
    use HasFactory;

    protected $fillable = [
        'penduduk_id',
        'kategori_id',
        'tanggal_denda',
        'jumlah_denda',
        'keterangan',
        'bukti_denda',
    ];


    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
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

}
