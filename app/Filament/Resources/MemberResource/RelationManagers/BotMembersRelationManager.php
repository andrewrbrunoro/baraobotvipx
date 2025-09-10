<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BotMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'botMembers';

    protected static ?string $recordTitleAttribute = 'bot_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bot_id')
                    ->label(__('Bot'))
                    ->relationship('bot', 'first_name')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bot.first_name')
                    ->label(__('Nome do Bot'))
                    ->searchable(),
                // Tables\Columns\TextColumn::make('expired_at')
                //     ->label(__('Data de expiração'))
                //     ->dateTime('d/m/Y H:i'),
                // Tables\Columns\TextColumn::make('already_kicked')
                //     ->label(__('Retirado do grupo')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Data de Início'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
