<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'chatMembers';

    protected static ?string $title = 'Assinaturas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_expired')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Expirado' : 'Ativo'),
                Tables\Columns\TextColumn::make('chat.name')
                    ->label('Chat')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
