<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChatMemberResource\Pages;
use App\Filament\Resources\ChatResource\Actions\AddMemberAction;
use App\Filament\Resources\ChatResource\Actions\RemoveMemberAction;
use App\Filament\Resources\ChatResource\Pages\ListChats;
use App\Models\ChatMember;
use App\Repositories\ChatRepository;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;

class ChatMemberResource extends Resource
{
    protected static ?string $model = ChatMember::class;

    protected static ?string $label = 'Assinatura';

    protected static ?string $pluralLabel = 'Assinaturas';

    protected static ?string $navigationIcon = 'polaris-make-payment-icon';

    protected static ?string $navigationGroup = 'Cobrança';

    public static function table(Table $table): Table
    {
        $chats = ChatRepository::make()
            ->userChats(
                auth()->user()->id,
            );

        return $table
            ->modifyQueryUsing(fn($query) => $query->whereIn('chat_id', $chats))
            ->columns([
                Tables\Columns\TextColumn::make('chat.name')
                    ->label(__('Grupo'))
                    ->url(fn($record): string => ListChats::getUrl(['query' => $record->name])),
                Tables\Columns\TextColumn::make('member.name')
                    ->label(__('Membro'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.username')
                    ->label(__('Username'))
                    ->searchable(),
                TextColumn::make('expired_at')
                    ->label(__('Tempo de duração'))
                    ->date('d/m/Y H:i'),
            ])
            ->filters([
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
                    }),
                Tables\Filters\Filter::make('expired_at')
                    ->label(__('Filtrar por data de expiração'))
                    ->form([
                        DatePicker::make('expired_from')
                            ->label(__('Expira a partir de'))
                            ->placeholder(__('Selecione a data inicial')),
                        DatePicker::make('expired_until')
                            ->label(__('Expira até'))
                            ->placeholder(__('Selecione a data final')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['expired_from'],
                                fn ($query) => $query->whereDate('expired_at', '>=', $data['expired_from'])
                            )
                            ->when(
                                $data['expired_until'],
                                fn ($query) => $query->whereDate('expired_at', '<=', $data['expired_until'])
                            );
                    })
            ])
            ->actions([
                AddMemberAction::make(),
                RemoveMemberAction::make(),
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

    public static function canDelete(Model $record): bool
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
            'index' => Pages\ListChatMembers::route('/')
        ];
    }
}
