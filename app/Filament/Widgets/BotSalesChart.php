<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Bot;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BotSalesChart extends ChartWidget
{
    protected static ?string $heading = 'Vendas por Bot';
    protected static ?int $sort = 2;

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function getData(): array
    {
        $month = $this->filter ?? now()->format('Y-m');
        $year = (int) substr($month, 0, 4);
        $month = (int) substr($month, 5, 2);

        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfDay();

        $bots = Bot::where('status', 'active')->get();
        $datasets = [];

        // Cores para cada bot
        $colors = [
            '#FF6384', // Vermelho
            '#36A2EB', // Azul
            '#FFCE56', // Amarelo
            '#4BC0C0', // Turquesa
            '#9966FF', // Roxo
            '#FF9F40', // Laranja
            '#C9CBCF', // Cinza
            '#7ED321', // Verde
            '#F5A623', // Laranja escuro
            '#50E3C2', // Verde água
            '#B8E986', // Verde claro
            '#4A90E2', // Azul claro
            '#9013FE', // Roxo escuro
            '#417505', // Verde escuro
            '#F8E71C', // Amarelo claro
        ];

        // Primeiro bot para pegar as datas
        $firstBot = $bots->first();
        if (!$firstBot) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $trend = Trend::query(Order::where('bot_id', $firstBot->id)->where('status', 'SUCCESS'))
            ->between($startDate, $endDate)
            ->perDay()
            ->count();

        // Filtrar datas futuras
        $labels = $trend->pluck('date')
            ->filter(fn($date) => Carbon::parse($date)->lte(now()))
            ->map(fn($date) => Carbon::parse($date)->format('d/m'))
            ->toArray();

        foreach ($bots as $index => $bot) {
            $botTrend = Trend::query(Order::where('bot_id', $bot->id)->where('status', 'SUCCESS'))
                ->between($startDate, $endDate)
                ->perDay()
                ->count();

            // Filtrar dados para mostrar apenas até o dia atual
            $data = $botTrend->pluck('aggregate')
                ->take(count($labels))
                ->toArray();

            $datasets[] = [
                'label' => $bot->username,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '40', // Adiciona transparência
                'tension' => 0.4,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        $months = [];
        $start = now()->subMonths(12);
        $end = now();

        while ($start <= $end) {
            $months[$start->format('Y-m')] = $start->format('F/Y');
            $start->addMonth();
        }

        return $months;
    }
}




