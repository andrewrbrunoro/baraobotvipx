<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Illuminate\Support\Collection;

class SellByHourWidget extends ChartWidget
{
    protected static ?string $heading = 'Vendas por hora';

    protected static ?string $description = 'Todas as vendas da semana por hora';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    private function getHours(): array
    {
        $hours = [];
        for ($i = 0; $i <= 23; $i++) {
            $hours[] = $i;
        }
        return $hours;
    }

    private function queryByDay(Carbon $date): Collection
    {
        return Trend::query(
            Order::query()
                ->where('status', PaymentStatusEnum::SUCCESS)
        )
            ->between(
                start: $date->copy()->startOfDay(),
                end: $date->copy()->endOfDay()
            )
            ->perHour()
            ->count();
    }

    protected function getData(): array
    {
        $yesterday = $this->queryByDay(today()->subDay());
        $today = $this->queryByDay(today());

        return [
            'datasets' => [
                [
                    'label' => 'Dia ' . today()->subDay()->format('d/m/Y'),
                    'data' => $yesterday->map(fn($item) => $item->aggregate),
//                    'backgroundColor' => '#ff5c00',
                    'borderColor' => '#f2b949',
                ],
                [
                    'label' => 'Dia ' . today()->format('d/m/Y'),
                    'data' => $today->map(fn($item) => $item->aggregate),
//                    'backgroundColor' => '#6a5acd',
                    'borderColor' => '#89f336',
                ],
            ],
            'labels' => self::getHours(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
