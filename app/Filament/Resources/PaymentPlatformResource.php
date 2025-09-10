<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PaymentPlatformEnum;
use App\Filament\Resources\PaymentPlatformResource\Actions\MercadoPagoAction;
use App\Filament\Resources\PaymentPlatformResource\Pages;
use App\Models\PaymentPlatform;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentPlatformResource extends Resource
{
    protected static ?string $model = PaymentPlatform::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $label = 'MercadoPago';

    protected static ?string $pluralLabel = 'MercadoPago';

    protected static ?string $navigationIcon = 'si-mercadopago';

    protected static ?string $navigationGroup = 'Configurações';

    protected static bool $shouldRegisterNavigation = false;

    public static function table(Table $table): Table
    {
        return $table
            ->configure()
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Plataforma'))
                    ->searchable(),
            ])
            ->actions([
                MercadoPagoAction::make()
                    ->hidden(fn($record) => $record->code !== PaymentPlatformEnum::MERCADO_PAGO),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentPlatforms::route('/')
        ];
    }
}
