<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatusEnum;
use App\Models\Member;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Illuminate\Support\Facades\DB;

class MemberOverview extends StatsOverviewWidget
{

    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $totalToday = DB::table('members')
            ->count();

        $totalMonth = Trend::model(Member::class)
            ->between(
                start: now()->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perMonth()
            ->count()
            ->first();

        $totalUserPayed = Trend::query(
            Order::query()
                ->where('status', PaymentStatusEnum::SUCCESS)
        )
            ->between(
                start: now()->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perMonth()
            ->count()
            ->first();

        return [
            Stat::make('Total de membros únicos', $totalToday),
            Stat::make('Total de membros no mês', $totalMonth->aggregate),
            Stat::make('Membro aprovados', $totalUserPayed->aggregate)
                ->color('success')
                ->description('Total deste mês')
                ->icon('heroicon-o-currency-dollar')
        ];
    }
}
