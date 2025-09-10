<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;

class SellChartWidget extends ChartWidget
{

    protected static ?string $heading = 'Vendas do mês';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $sell = Trend::query(
            Order::query()
                ->where('status', PaymentStatusEnum::SUCCESS)
        )
            ->between(
                start: $start,
                end: $end,
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Vendas do mês',
                    'data' => $sell->map(fn($item) => $item->aggregate),
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                    ],
                    'borderColor' => [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $sell->map(fn($item) => Carbon::createFromFormat('Y-m-d', $item->date)->format('d')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
