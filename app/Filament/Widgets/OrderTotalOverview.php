<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderTotalOverview extends StatsOverviewWidget
{

    protected static ?string $pollingInterval = '5m';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $total = Order::where('user_id', $this->getUserId())
            ->where('status', PaymentStatusEnum::SUCCESS)
            ->whereMonth('created_at', now()->month)
            ->get()
            ->sum('real_total');

        $totalWaiting = Order::where('user_id', $this->getUserId())
            ->where('status', PaymentStatusEnum::WAITING)
            ->whereMonth('created_at', now()->month)
            ->get()
            ->sum('real_total');

        return [
            Stat::make(sprintf('Finalizado mês %s', now()->month), 'R$ ' . number_format($total, 2)),
            Stat::make(sprintf('Aguardando mês %s', now()->month), 'R$ ' . number_format($totalWaiting, 2)),
        ];
    }

    private function getUserId(): int
    {
        return auth()->user()->id;
    }
}
