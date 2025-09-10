<?php

namespace App\Filament\Widgets;

use App\Models\MemberLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MemberLogOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Logs de membros';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MemberLog::query()
                    ->where('name', '<>', '')
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('options'),
            ]);
    }
}
