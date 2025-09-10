<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Remarketing;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RemarketingEffectWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        // Total de remarketings
        $totalRemarketings = Remarketing::count();

        // Pedidos com sucesso de membros que receberam remarketing
        $successfulOrders = Order::whereIn('member_id', function($query) {
            $query->select('member_id')
                  ->from('remarketings');
        })
        ->where('status', PaymentStatusEnum::SUCCESS)
        ->get();

        $successfulMembersCount = $successfulOrders->unique('member_id')->count();
        $totalRevenue = $successfulOrders->sum('total');

        // Taxa de conversão
        $conversionRate = $totalRemarketings > 0 
            ? round(($successfulMembersCount / $totalRemarketings) * 100, 2)
            : 0;

        return [
            Stat::make('Remarketings Enviados', $totalRemarketings)
                ->description('Total de campanhas de remarketing')
                ->icon('heroicon-o-megaphone')
                ->color('info'),

            Stat::make('Membros Convertidos', $successfulMembersCount)
                ->description("Taxa de conversão: {$conversionRate}%")
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Receita Gerada', 'R$ ' . number_format($totalRevenue, 2, ',', '.'))
                ->description('Valor total de pedidos com sucesso')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
