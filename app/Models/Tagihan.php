<?php

namespace App\Models;

use App\Filament\Resources\TagihanResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tagihan extends Model
{
    use HasFactory;

    protected $fillable = [
        'penduduk_id',
        'bulan_tagihan',
        'tahun_tagihan',
        'meteran_awal',
        'meteran_akhir',
        'tagihan_meteran',
        'total_tagihan',
        'status_tagihan',
        'harga_air_permeter_id',
    ];

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    protected static function booted()
    {
        static::saving(function ($tagihan) {
            // Memeriksa apakah 'meteran_awal' dan 'meteran_akhir' tidak null
            if ($tagihan->meteran_awal !== null && $tagihan->meteran_akhir !== null) {
                // Menghitung 'tagihan_meteran' sebagai selisih antara 'meteran_akhir' dan 'meteran_awal'
                $tagihan->tagihan_meteran = $tagihan->meteran_akhir - $tagihan->meteran_awal;
            }
        });
    }

    public function validateBulanTagihan()
    {
        $pendudukId = $this->penduduk_id;
        $bulan = $this->bulan_tagihan;
        $tahun = $this->tahun_tagihan;

        $bulanAngka = TagihanResource::convertBulanToAngka($bulan);

        $lastTagihan = Tagihan::where('penduduk_id', $pendudukId)
            ->where(function ($query) use ($tahun, $bulanAngka) {
                $query->where('tahun_tagihan', '<', $tahun)
                      ->orWhere(function ($query) use ($tahun, $bulanAngka) {
                          $query->where('tahun_tagihan', $tahun)
                                ->whereRaw("FIELD(bulan_tagihan, 'Januari', 'Februari',
                                'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                                'September', 'Oktober', 'November', 'Desember') < ?", [$bulanAngka]);
                      });
            })
            ->orderBy('tahun_tagihan', 'desc')
            ->orderByRaw("FIELD(bulan_tagihan, 'Desember', 'November',
            'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei',
            'April', 'Maret', 'Februari', 'Januari')")
            ->first();

        if ($lastTagihan) {
            $bulanSebelumnya = $lastTagihan->bulan_tagihan;
            $bulanSebelumnyaAngka = TagihanResource::convertBulanToAngka($bulanSebelumnya);

            if ($bulanAngka !== $bulanSebelumnyaAngka + 1) {
                return false;
            }
        }

        return true;
    }


    public function hargaAirPerMeter()
    {
    return $this->belongsTo(HargaAirPermeter::class, 'harga_air_permeter_id');
    }

}
