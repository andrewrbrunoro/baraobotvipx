<?php declare(strict_types=1);

namespace App\Filament\Resources\ChatResource\Pages;

use App\Filament\Resources\ChatMemberResource;
use App\Filament\Resources\ChatResource\Actions\AddMemberAction;
use App\Filament\Resources\ChatResource\Actions\RemoveMemberAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListChatMembers extends ListRecords
{
    protected static string $resource = ChatMemberResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.code')
                    ->label(__('Cód'))
                    ->searchable(),
                TextColumn::make('member.name')
                    ->label(__('Nome')),
                TextColumn::make('member.username')
                    ->label(__('Username')),
                TextColumn::make('expired_at')
                    ->label(__('Válido até'))
                    ->date('d/m/Y H:i'),
                TextColumn::make('created_at')
                    ->label(__('Data'))
                    ->date('d/m/Y H:i'),
            ])
            ->actions([
                AddMemberAction::make(),
                RemoveMemberAction::make(),
            ]);
    }
}
