<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $label = 'Membro';

    protected static ?string $pluralLabel = 'Membros';

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Minha Loja';

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
            ->modifyQueryUsing(fn($query) => $query->newQuery())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Cód'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nome'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label(__('Username'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('chatMember.expired_at')
                    ->label(__('Membro'))
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Sem Assinatura';
                        return now()->lt($state) ? 'Ativo' : 'Inativo';
                        // return 'Ativo';
                    })
                    ->color(function ($state) {
                        if (!$state) return 'danger';
                        return $state === 'Ativo' ? 'success' : 'danger';
                    }),
                Tables\Columns\TextColumn::make('chatMember.expired_at')
                    ->label(__('Expira em'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('chat')
                    ->label(__('Chat'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (Member $record): string => route('filament.admin.resources.members.chat', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_subscription')
                    ->label(__('Assinaturas Ativas'))
                    ->query(fn ($query) => $query->whereHas('chatMember', fn ($q) => $q->where('expired_at', '>', now()))),
                Tables\Filters\Filter::make('expired_subscription')
                    ->label(__('Assinaturas Expiradas'))
                    ->query(fn ($query) => $query->whereHas('chatMember', fn ($q) => $q->where('expired_at', '<=', now()))),
                Tables\Filters\Filter::make('never_subscribed')
                    ->label(__('Nunca Assinaram'))
                    ->query(fn ($query) => $query->whereDoesntHave('chatMember')),
                Tables\Filters\Filter::make('created_at')
                    ->label(__('Filtrar por data de criação'))
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'botMembers' => \App\Filament\Resources\MemberResource\RelationManagers\BotMembersRelationManager::class,
            'orders' => \App\Filament\Resources\MemberResource\RelationManagers\OrdersRelationManager::class,
            'logs' => \App\Filament\Resources\MemberResource\RelationManagers\LogsRelationManager::class,
            'subscriptions' => \App\Filament\Resources\MemberResource\RelationManagers\SubscriptionsRelationManager::class,
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'chat' => Pages\ChatMember::route('/{record}/chat'),
        ];
    }
}
