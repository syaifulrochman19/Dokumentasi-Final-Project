<?php

namespace App\Filament\Widgets;

use App\Models\Pembayaran;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Collection;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WidgetPengeluaranChart extends ChartWidget
{
    use InteractsWithPageFilters;
    protected static ?string $heading = 'Statistik Pengeluaran';
    protected static string $color = 'danger';

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? Carbon::now()->startOfYear();
        $endDate = $this->filters['endDate'] ?? Carbon::now()->endOfYear();


        $data = Trend::model(Pembayaran::class)
            ->between(
                start: Carbon::parse($startDate),
                end: Carbon::parse($endDate),
            )
            ->dateColumn('tanggal_pengeluaran')
            ->perDay()
            ->sum('jumlah_pengeluaran');

        // Adding missing dates in the data
        $allDates = $this->getDateRange(Carbon::parse($startDate), Carbon::parse($endDate));
        $data = $allDates->map(function ($date) use ($data) {
            $value = $data->firstWhere('date', $date);
            return new TrendValue(
                date: $date,
                aggregate: $value ? $value->aggregate : 0
            );
        });


        return [
            'datasets' => [
                [
                    'label' => 'Pengeluaran per Hari',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
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












