<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PaymentStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Jobs\Telegram\AddMemberJob;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Auth;
use Filament\Forms\Components\DatePicker;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $label = 'Pedido';

    protected static ?string $pluralLabel = 'Pedidos';

    protected static ?string $navigationIcon = 'bi-coin';

    protected static ?string $navigationGroup = 'Cobrança';

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
            ->modifyQueryUsing(fn($query) => $query->where('user_id', auth()->user()->id))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Pedido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform_id')
                    ->label('ID MP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.code')
                    ->label(__('Cód'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.name')
                    ->label(__('Membro'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('bot.first_name')
                    ->label(__('Bot'))
                    ->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label(__('Status'))
                    ->options([
                        PaymentStatusEnum::WAITING => PaymentStatusEnum::legend(PaymentStatusEnum::WAITING),
                        PaymentStatusEnum::SUCCESS => PaymentStatusEnum::legend(PaymentStatusEnum::SUCCESS),
                        PaymentStatusEnum::FAILURE => PaymentStatusEnum::legend(PaymentStatusEnum::FAILURE),
                    ])
                    ->afterStateUpdated(function($state, Model $record) {
                        $product = Product::find($record->product_id);

                        if ($record->item_type === Chat::class && $state === PaymentStatusEnum::SUCCESS) {
                            $dataDurationTime = $product->duration_time;

                            if ($dataDurationTime === 0)
                                $expiredAt = today()->clone()->addYears(5);
                            else {
                                $expiredAt = today()->clone()->addSeconds($dataDurationTime);
                            }

                            dispatch(
                                new AddMemberJob([
                                    'member_id' => $record->member_id,
                                    'chat_id' => $record->item_id,
                                    'expired_at' => $expiredAt,
                                ])
                            );
                        }
                    })
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Procurar por status'))
                    ->multiple()
                    ->options([
                        PaymentStatusEnum::WAITING => PaymentStatusEnum::legend(PaymentStatusEnum::WAITING),
                        PaymentStatusEnum::SUCCESS => PaymentStatusEnum::legend(PaymentStatusEnum::SUCCESS),
                        PaymentStatusEnum::FAILURE => PaymentStatusEnum::legend(PaymentStatusEnum::FAILURE),
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->label(__('Filtrar por data'))
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('Data inicial'))
                            ->placeholder(__('Selecione a data inicial')),
                        DatePicker::make('created_until')
                            ->label(__('Data final'))
                            ->placeholder(__('Selecione a data final')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query) => $query->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query) => $query->whereDate('created_at', '<=', $data['created_until'])
                            );
                    })
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
