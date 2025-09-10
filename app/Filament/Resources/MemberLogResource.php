<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberLogResource\Pages;
use App\Filament\Resources\MemberLogResource\RelationManagers;
use App\Models\MemberLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
class MemberLogResource extends Resource
{
    protected static ?string $model = MemberLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('member.code')
                    ->label('Codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('options'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label(__('Chat'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (MemberLog $record): string => route('filament.admin.resources.members.chat', ['record' => $record->member->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberLogs::route('/'),
            'create' => Pages\CreateMemberLog::route('/create'),
            'edit' => Pages\EditMemberLog::route('/{record}/edit'),
        ];
    }
}
