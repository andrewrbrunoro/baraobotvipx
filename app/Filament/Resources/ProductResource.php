<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentPlatformResource\Pages\ListPaymentPlatforms;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\GridsRelationManager;
use App\Models\Chat;
use App\Models\Product;
use App\Repositories\ChatRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\UserPaymentIntegrationRepository;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Leandrocfe\FilamentPtbrFormFields\Money;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $label = 'Produto';

    protected static ?string $pluralLabel = 'Produtos';

    protected static ?string $navigationIcon = 'polaris-product-cost-icon';

    protected static ?string $navigationGroup = 'Minha Loja';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $dayInSeconds = 86400;

        $chats = Chat::where('user_id', auth()->user()->id)
            ->pluck('name', 'id');

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('model_id')
                                    ->label(__('Selecione o Chat'))
                                    ->options($chats)
                                    ->required()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $chat = ChatRepository::make()
                                            ->find(auth()->user()->id, (int)$state);

                                        if (!$chat)
                                            abort(404);

                                        if (empty($get('name'))) {
                                            $set('name', sprintf('Ticket de acesso ao grupo %s', $chat->name));
                                        }
                                        $set('code', $chat->id);
                                    })
                                    ->live()
                            ]),

                        TextInput::make('name')
                            ->label(__('Título/Nome'))
                            ->required(),

                        Grid::make()
                            ->schema([
                                Hidden::make('code')
                                    ->label(__('Código'))
                                    ->required(),
                                Select::make('duration_time')
                                    ->label(__('Tempo de duração'))
                                    ->helperText(__('Esse campo define quanto tempo o usuário terá acesso ao produto'))
                                    ->default(0)
                                    ->options([
                                        0 => __('Aquisição única'),
                                        $dayInSeconds => __('1 dia'),
                                        $dayInSeconds * 7 => __('1 Semana'),
                                        $dayInSeconds * 15 => __('15 dias'),
                                        $dayInSeconds * 30 => __('1 Mês'),
                                        $dayInSeconds * 90 => __('3 Meses'),
                                        $dayInSeconds * 365 => __('1 Ano'),
                                    ])
                            ]),

                        Grid::make()
                            ->schema([
                                Money::make('price')
                                    ->label(__('Preço')),
                                Money::make('price_sale')
                                    ->label(__('Preço Promoção')),
                            ]),

                        MarkdownEditor::make('description')
                            ->label(__('Descrição'))
                            ->toolbarButtons([
                                'bold',          // <b>bold</b>, <strong>bold</strong>, **bold**
                                'italic',        // <i>italic</i>, <em>italic</em>, *italic*
                                'codeBlock',     // <code>code</code>, `code`
                                'strike',        // <s>strike</s>, <strike>strike</strike>, <del>strike</del>, ~~strike~~
                                'underline',     // <u>underline</u>
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn($query) => $query->where('user_id', auth()->user()->id)->whereNull('parent_id'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Título/Nome'))
                    ->searchable(),
                TextColumn::make('duration_time')
                    ->label(__('Tempo de duração'))
                    ->formatStateUsing(function ($state) {
                        if ($state === 0) {
                            return 'Aquisição única';
                        }

                        return $state / 86400 . ' dias';
                    }),
                TextColumn::make('price')
                    ->label(__('Preço')),
                TextColumn::make('price_sale')
                    ->label(__('Preço Promoção')),
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

    public static function hasPayment(): void
    {

        $hasIntegration = UserPaymentIntegrationRepository::make()
            ->hasIntegration(auth()->user()->id);

        if (!$hasIntegration) {
            Notification::make()
                ->danger()
                ->body(__('Para criar um produto é necessário ter uma forma de pagamento.'))
                ->send();

            redirect(ListPaymentPlatforms::getUrl(['has_integration' => 'no']));
        }
    }

    public static function canCreate(): bool
    {
        static::hasPayment();

        return parent::canCreate(); // TODO: Change the autogenerated stub
    }

    public static function getRelations(): array
    {
        return [
            GridsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
