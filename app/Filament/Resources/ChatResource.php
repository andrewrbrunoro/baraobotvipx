<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChatResource\Pages;
use App\Models\Chat;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChatResource extends Resource
{
    protected static ?string $model = Chat::class;

    protected static ?string $label = 'Gerenciar Grupos/Canais';

    protected static ?string $pluralLabel = 'Grupos/Canais';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Minha Loja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading(__('Dados do Chat'))
                    ->description(__('Ao adicionar PREÇO automaticamente o GRUPO irá ficar restrito de qualquer ação dos MEMBROS'))
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('status')
                                    ->required()
                                    ->default('ON')
                                    ->options([
                                        'ON' => __('Ativado'),
                                        'OFF' => __('Desativado'),
                                    ])
                            ]),

                        TextInput::make('name')
                            ->label(__('Título Nome do Grupo'))
                            ->required(),
                    ]),
//                Section::make()
//                    ->heading(__('Permissões'))
//                    ->description(__('As permissões afetam exclusivamente MEMBROS'))
//                    ->schema([
//                        Grid::make()
//                            ->relationship('permissions')
//                            ->schema([
//                                Toggle::make('can_send_messages')
//                                    ->label(__('Mensagens'))
//                                    ->helperText(__('Os membros podem enviar mensagens de texto, cotnatos, cobranças e outros...')),
//                                Toggle::make('can_send_audios')
//                                    ->label(__('Áudios'))
//                                    ->helperText(__('Os membros podem enviar mensagens de áudio')),
//                                Toggle::make('can_send_documents')
//                                    ->label(__('Documentos'))
//                                    ->helperText(__('Os membros podem enviar mensagens de documentos')),
//                                Toggle::make('can_send_photos')
//                                    ->label(__('Fotos'))
//                                    ->helperText(__('Os membros podem enviar fotos')),
//                                Toggle::make('can_send_videos')
//                                    ->label(__('Videos'))
//                                    ->helperText(__('Os membros podem enviar vídeos')),
//                                Toggle::make('can_send_video_notes')
//                                    ->label(__('Vídeos rápido'))
//                                    ->helperText(__('Os membros podem enviar vídeos rápidos')),
//                                Toggle::make('can_send_voice_notes')
//                                    ->label(__('Áudio rápido'))
//                                    ->helperText(__('Os membros podem enviar áudios rápido')),
//                                Toggle::make('can_send_polls')
//                                    ->label(__('Pesquisas'))
//                                    ->helperText(__('Os membros podem criar pesquisas')),
//                                Toggle::make('can_send_other_messages')
//                                    ->label(__('Outros tipos de mensagens'))
//                                    ->helperText(__('Os membros podem animações, jogos, stickers e BOTs')),
//                                Toggle::make('can_add_web_page_previews')
//                                    ->label(__('Links/Páginas Web'))
//                                    ->helperText(__('Os membros podem enviar mensagens com links/páginas')),
//                                Toggle::make('can_change_info')
//                                    ->label(__('Alterar informações'))
//                                    ->helperText(__('Os membros podem alterar informações do grupo')),
//                                Toggle::make('can_invite_users')
//                                    ->label(__('Convidar usuários'))
//                                    ->helperText(__('Os membros podem convidar usuários')),
//                                Toggle::make('can_pin_messages')
//                                    ->label(__('Destacar mensagens'))
//                                    ->helperText(__('Os membros podem destacar mensagens no grupo')),
//                            ])
//                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('user_id', auth()->user()->id))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Grupo'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label(__('Membros'))
                    ->counts('members'),
                Tables\Columns\TextColumn::make('bot.bot.first_name')
                    ->label(__('Bot')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('member_manager')
                    ->label(__('Administrar Membros'))
                    ->iconPosition(IconPosition::Before)
                    ->icon('heroicon-m-squares-plus')
                    ->color('success')
                    ->url(fn($record) => self::getUrl('members', ['record' => $record->id])),
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->id !== $record->user_id) return false;

        return parent::canEdit($record); // TODO: Change the autogenerated stub
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChats::route('/'),
            'create' => Pages\CreateChat::route('/create'),
            'edit' => Pages\EditChat::route('/{record}/edit'),
            'members' => Pages\ListChatMembers::route('/{record}/members'),
        ];
    }
}
