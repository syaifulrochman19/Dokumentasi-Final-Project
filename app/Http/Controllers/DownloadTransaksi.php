<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;

class DownloadTransaksi extends Controller
{
    public function download($id)
    {
        $transaksi = Transaksi::with(['tagihan.penduduk', 'kategori'])->findOrFail($id);

        // Mendapatkan tanggal saat ini dalam format yang diinginkan
        $currentDate = date('Y_m_d');

        $pdf = Pdf::loadView('transaksi.nota', ['transaksi' => $transaksi]);

        // Menyusun nama file sesuai dengan format yang diinginkan
        $fileName = "Nota-Transaksi-{$id}_{$currentDate}.pdf";

        return $pdf->download($fileName);
    }
}
