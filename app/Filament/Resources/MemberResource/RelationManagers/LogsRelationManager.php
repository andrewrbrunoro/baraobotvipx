<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberLogs';

    protected static ?string $title = 'Logs';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Mensagem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('options')
                    ->label('Options')
            ])
            ->defaultSort('created_at', 'desc');
    }
}
