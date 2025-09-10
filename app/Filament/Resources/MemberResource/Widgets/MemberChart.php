<?php declare(strict_types=1);

namespace App\Filament\Resources\MemberResource\Widgets;

use App\Models\Member;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class MemberChart extends ChartWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?string $heading = 'Membros';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $description = 'Membros diariamente';

    protected function getData(): array
    {
        $data = Trend::model(Member::class)
            ->between(
                start: today()->clone()->subDay(),
                end: today()->endOfDay()
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Novos membros',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ]
            ],
            'labels' => $data->map(fn (TrendValue $value) => Carbon::createFromFormat('Y-m-d', $value->date)->format('d/m/Y')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
