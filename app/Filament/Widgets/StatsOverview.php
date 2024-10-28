<?php

namespace App\Filament\Widgets;

use App\Models\Denda;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Pembayaran;
use App\Models\Penduduk;
use App\Models\Transaksi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use NumberFormatter;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    protected function getStats(): array
    {
        // $startDate = ! is_null($this->filters['startDate'] ?? null) ?
        // Carbon::parse($this->filters['startDate']) :
        // null;

        // $endDate = ! is_null($this->filters['endDate'] ?? null) ?
        // Carbon::parse($this->filters['endDate']) :
        // now();

        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

         // Mengambil jumlah pemasukan dari Transaksi dalam rentang tanggal yang dipilih
        $pemasukanTransaksi = Transaksi::incomes()
            ->when($startDate, function ($query) use ($startDate, $endDate) {
            $query->whereBetween('tanggal_pembayaran', [$startDate, $endDate]);
        })
            ->sum('jumlah_pembayaran');

        // Mengambil jumlah pemasukan dari Pembayaran dalam rentang tanggal yang dipilih
        $pemasukanDenda = Denda::whereHas('kategori', function ($query) {
            $query->where('pemasukan', true);
        })
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_denda', [$startDate, $endDate]);
            })
            ->sum('jumlah_denda');

        // Menggabungkan pemasukan dari kedua sumber
        $totalPemasukan = $pemasukanTransaksi + $pemasukanDenda;

        // Mengambil jumlah pengeluaran dari Pembayaran dalam rentang tanggal yang dipilih
        $pengeluaran = Pembayaran::expenses()
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);
            })
            ->sum('jumlah_pengeluaran');

     $selisih = $totalPemasukan - $pengeluaran;


        // Membuat formatter untuk mata uang
        $formatter = new NumberFormatter('id_ID', NumberFormatter::CURRENCY);

        return [
            Stat::make('Total Pemasukan', $formatter->formatCurrency($totalPemasukan, 'IDR'))
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Total Pengeluaran', $formatter->formatCurrency($pengeluaran, 'IDR'))
                ->color('danger')
                ->icon('heroicon-s-credit-card'),
            Stat::make('Saldo', $formatter->formatCurrency($selisih, 'IDR'))
                ->color($selisih >= 0 ? 'success' : 'danger')
                ->icon($selisih >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),
        ];
    }
}
