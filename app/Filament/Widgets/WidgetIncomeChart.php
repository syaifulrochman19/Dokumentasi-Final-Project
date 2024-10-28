<?php

namespace App\Filament\Widgets;

use App\Models\Denda;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Filament\Widgets\Concerns\InteractsWithPageFilters;



class WidgetIncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Pemasukan';
    protected static string $color = 'success';

    use InteractsWithPageFilters;

    protected function getData(): array
    {


        $startDate = $this->filters['startDate'] ?? Carbon::now()->startOfYear();
        $endDate = $this->filters['endDate'] ?? Carbon::now()->endOfYear();

        // Trend data for Transaksi
        $transaksiData = Trend::model(Transaksi::class)
            ->between(
                start: Carbon::parse($startDate),
                end: Carbon::parse($endDate),
            )
            ->dateColumn('tanggal_pembayaran')
            ->perDay()
            ->sum('jumlah_pembayaran');

        // Manually get Denda data
        $dendaData = Denda::whereHas('kategori', function ($query) {
            $query->where('pemasukan', true);
        })
        ->whereBetween('tanggal_denda', [Carbon::parse($startDate), Carbon::parse($endDate)])
        ->get()
        ->groupBy(function ($date) {
            return Carbon::parse($date->tanggal_denda)->format('Y-m-d');
        })
        ->map(function ($row) {
            return $row->sum('jumlah_denda');
        });

        // Combine both datasets
        $combinedData = $transaksiData->map(function (TrendValue $transaksiValue) use ($dendaData) {
            $date = $transaksiValue->date;
            $pembayaranValue = $dendaData->get($date, 0);

            $total = $transaksiValue->aggregate + $pembayaranValue;

            return new TrendValue(
                date: $date,
                aggregate: $total
            );
        });


        $allDates = $this->getDateRange(Carbon::parse($startDate), Carbon::parse($endDate));
        $combinedData = $allDates->map(function ($date) use ($combinedData) {
            $value = $combinedData->firstWhere('date', $date);
            return new TrendValue(
                date: $date,
                aggregate: $value ? $value->aggregate : 0
            );
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan per Hari',
                    'data' => $combinedData->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],

            'labels' => $combinedData->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getDateRange(Carbon $start, Carbon $end): Collection
    {
        $dates = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }
        return collect($dates);
    }
}






