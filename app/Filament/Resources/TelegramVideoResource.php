<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramVideoResource\Pages;
use App\Models\TelegramVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramVideoResource extends Resource
{

    protected static ?string $label = 'Vídeo';

    protected static ?string $pluralLabel = 'Vídeos';

    protected static ?string $model = TelegramVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nome/Título'))
                            ->required()
                            ->maxLength(60),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramVideos::route('/'),
            'create' => Pages\CreateTelegramVideo::route('/create'),
            'edit' => Pages\EditTelegramVideo::route('/{record}/edit'),
        ];
    }
}
