<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BotResource\Pages;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Repositories\BotRepository;
use App\Repositories\CommandRepository;
use App\Services\Messengers\Telegram\Commands\HelpCommand;
use App\Services\Messengers\Telegram\Commands\HowToSetupGroup;
use App\Services\Messengers\Telegram\Commands\ProductListCommand;
use App\Services\Messengers\Telegram\Commands\StartCommand;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Telegram\Bot\Exceptions\TelegramResponseException;

class BotResource extends Resource
{
    protected static ?string $model = Bot::class;

    protected static ?string $label = 'Bot';

    protected static ?string $pluralLabel = 'Bots';

    protected static ?string $navigationIcon = 'lucide-bot-message-square';

    protected static ?string $navigationGroup = 'Telegram';

    private static array $defaultCommands = [
        'help',
        'start',
        'how_to_setup_group',
        'setup_group',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make()
                    ->schema([
                        Forms\Components\Wizard\Step::make(__('Dados do BOT'))
                            ->schema(self::botForm())
                            ->afterValidation(function (array $state, Forms\Set $set) {
                                $user = auth()->user();
                                $ownerCode = $user->telegram_owner_code;

                                $create = (new BotRepository())
                                    ->create($state['code'], [
                                        ...$state,
                                        'owner_code' => $ownerCode,
                                    ]);

                                $notification = Notification::make();

                                if (!$create) {
                                    $notification
                                        ->danger()
                                        ->body(__('Não foi possível salvar o BOT, tente novamente.'))
                                        ->send();
                                } else {

                                    $notification
                                        ->success()
                                        ->body(__('BOT salvo com sucesso!'));

                                    $token = $create->token;
                                    $botTelegram = BotTelegram::make($token);

                                    $botTelegram->manageCommand()
                                        ->setCommands([
                                            StartCommand::class,
                                            HelpCommand::class,
                                            HowToSetupGroup::class,
                                            ProductListCommand::class,
                                        ]);

                                    $botTelegram
                                        ->webhook()
                                        ->set(self::getWebhookUrl($token));

                                    $notification->send();

                                    redirect()
                                        ->route(Pages\EditBot::getRouteName(), ['step' => 'adicionar-comandos', 'record' => $create->id]);
                                }
                            }),
                        Forms\Components\Wizard\Step::make(__('Adicionar Comandos'))
                            ->schema(self::groupForm())
                            ->afterValidation(function (array $state) {

                                $commands = $state['commands'] ?? false;

                                if (!$commands) {
                                    $commands = CommandRepository::make()
                                        ->available(auth()->user()->id, ['command_without_user']);

                                    foreach ($commands->whereIn('name', static::$defaultCommands) as $command) {

                                        if (!$command->id) continue;

                                        BotCommand::firstOrCreate([
                                            'bot_id' => $state['id'],
                                            'command_id' => $command->id,
                                        ]);
                                    }
                                } else {
                                    BotCommand::where('bot_id', $state['id'])
                                        ->delete();
                                    foreach ($commands as $command) {
                                        BotCommand::firstOrCreate([
                                            'bot_id' => $state['id'],
                                            'command_id' => $command['command_id'],
                                        ]);
                                    }
                                }
                            }),
                        Forms\Components\Wizard\Step::make(__('Sumário'))
                            ->schema(self::summary($form))
                    ])
                    ->persistStepInQueryString()
                    ->columnSpanFull()
                    ->previousAction(fn(Forms\Components\Actions\Action $action) => $action->label(__('Voltar')))
                    ->nextAction(fn(Forms\Components\Actions\Action $action) => $action->label(__('Continuar')))
                    ->submitAction(new HtmlString('<a style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action" href="/admin/bots">Finalizar</a>'))
            ]);
    }

    public static function summary(Form $form): array
    {
        $bot = $form->model;
        if (!($bot instanceof Bot))
            return [];

        $isVerified = $bot->is_verified;
        $hasChat = $bot->chats->count();

        return [
            Forms\Components\ViewField::make('summary')
                ->view('bot.summary', [
                    'list' => [
                        [
                            'title' => 'Bot Verificado',
                            'icon' => !$isVerified ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge',
                            'text' => !$isVerified
                                ? new HtmlString(sprintf('Verifique seu BOT <a class="text-primary-600 dark:text-primary-400" target="_blank" href="https://t.me/%s?text=/start">clicando aqui</a>', $bot->username))
                                : 'O BOT está verificado',
                        ],
                        [
                            'title' => 'Chats vinculado',
                            'icon' => !$hasChat ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge',
                            'text' => !$hasChat
                                ? new HtmlString(sprintf('Após verificar seu BOT adicione ele em algum grupo <a class="text-primary-600 dark:text-primary-400" target="_blank" href="https://t.me/%s?startgroup=/start">clicando aqui</a>', $bot->username))
                                : sprintf('O BOT está vinculado a %s %s', $hasChat, $hasChat > 1 ? 'grupos' : 'grupo')
                        ]
                    ]
                ])
        ];
    }

    public static function groupForm(): ?array
    {
        $commands = CommandRepository::make()
            ->available(auth()->user()->id, ['command_without_user']);

        $form = [
            Forms\Components\ViewField::make('command_defaults')
                ->view('alert', [
                    'text' => new HtmlString(<<<HTML
                    Alguns comandos já estão setados como padrão: <br><br>

                    <strong>/help</strong> - Lista todos os comandos disponíveis <br>
                    <strong>/start</strong> - Comando necessário para iniciar a conversa com o BOT  <br>
                    <strong>/products</strong> - Lista todos os seus produtos disponíveis  <br>
                    <strong>/setup_group</strong> - Comando para que o BOT tenha permissão de gerenciar o grupo
                    HTML
                    )
                ]),
        ];

        $form[] = Forms\Components\Repeater::make('commands')
            ->label(__('Disponibilizar comandos'))
            ->relationship('commands')
            ->deletable(false)
            ->schema([
                Forms\Components\Select::make('command_id')
                    ->label(__('Selecione o comando para disponibilizar no BOT'))
                    ->searchable()
                    ->options($commands->pluck('select_name', 'id'))
            ]);

        return $form;
    }

    public static function botForm(): array
    {
        $helperLinkBotToken = 'https://www.siteguarding.com/en/how-to-get-telegram-bot-api-token';

        return [
            Forms\Components\TextInput::make('token')
                ->label(__('Chave/Token do BOT'))
                ->placeholder(__('Cole o TOKEN do BOT aqui e clique na lupa'))
                ->helperText(new HtmlString(__('Caso não saiba buscar os dados, <a href="' . $helperLinkBotToken . '" class="text-danger-600" target="_blank">clique aqui</a>.')))
                ->unique(ignoreRecord: true)
                ->prefixAction(
                    Forms\Components\Actions\Action::make('bot_data')
                        ->icon('heroicon-s-magnifying-glass')
                        ->action(function (Forms\Get $get, Forms\Set $set) {
                            $user = auth()->user();
//                            $ownerCode = $user->telegram_owner_code;

                            $notification = Notification::make();

                            try {
                                $token = $get('token');

                                $result = BotTelegram::make($token)
                                    ->getMe();

                                if ($result->is_bot !== true) {
                                    $notification
                                        ->info()
                                        ->body(__('Esse TOKEN não é de um BOT!'))
                                        ->send();

                                    return;
                                }

                                $set('code', $result->id);

                                $set('first_name', $result->first_name);
                                $set('username', $result->username);
                                $set('can_join_groups', $result->can_join_groups);
                                $set('can_read_all_group_messages', $result->can_read_all_group_messages);
                                $set('supports_inline_queries', $result->supports_inline_queries);
                                $set('can_connect_to_business', $result->can_connect_to_business);
                                $set('has_main_web_app', $result->has_main_web_app);

                                $notification
                                    ->success()
                                    ->body(__('Os dados foram preenchidos de acordo com as configurações do BOT'));
                            } catch (TelegramResponseException $e) {
                                if ($e->getCode() === 401) {
                                    $notification->danger()
                                        ->body(__('TOKEN inválido, por favor, verifique se digitou o TOKEN correto.'));
                                }
                            }


                            $notification->send();
                        }),
                ),

            Forms\Components\Split::make([
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('code')
                        ->label(__('Código do BOT'))
                        ->helperText(fn(Forms\Get $get): string => !$get('token') ? __('Preencha o TOKEN do BOT para o dado ser preenchido automaticamente') : '')
                        ->required()
                        ->readOnly(),
                    Forms\Components\TextInput::make('first_name')
                        ->label(__('Nome do BOT'))
                        ->helperText(fn(Forms\Get $get): string => !$get('token') ? __('Preencha o TOKEN do BOT para o dado ser preenchido automaticamente') : '')
                        ->required()
                        ->readOnly(),
                    Forms\Components\TextInput::make('username')
                        ->label(__('Username'))
                        ->helperText(fn(Forms\Get $get): string => !$get('token') ? __('Preencha o TOKEN do BOT para o dado ser preenchido automaticamente') : '')
                        ->required()
                        ->readOnly(),
                ]),
                Forms\Components\Section::make([
                    Forms\Components\Toggle::make('can_join_groups')
                        ->label(__('Recebe convite para grupos?'))
                        ->helperText(__('O BOT pode receber convites para grupos.'))
                        ->disabled(),
                    Forms\Components\Toggle::make('can_read_all_group_messages')
                        ->label(__('Ler todas as mensagens do grupo?'))
                        ->helperText(__('O BOT pode ler todas as mensagens dos grupos em que ele está vinculado.'))
                        ->disabled(),
                    Forms\Components\Toggle::make('supports_inline_queries')
                        ->label(__('Consulta em linhas?'))
                        ->helperText(__('O BOT pode fazer consultar em linha.'))
                        ->disabled(),
                    Forms\Components\Toggle::make('can_connect_to_business')
                        ->label(__('Telegram Business?'))
                        ->helperText(__('O BOT tem acesso a recursos do Telegram.'))
                        ->disabled(),
                    Forms\Components\Toggle::make('has_main_web_app')
                        ->label(__('WebApp?'))
                        ->helperText(__('O BOT pode usar o Web App'))
                        ->disabled(),
                ])->grow(false),
            ])
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('user_id', auth()->user()->id))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verificado')
                    ->falseColor('danger')
                    ->trueColor('success')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ])
                    ->default('active'),
                TextColumn::make('code')
                    ->label('Código'),
                TextColumn::make('first_name')
                    ->label('Título'),
                TextColumn::make('username')
                    ->label('Usuário'),
                SelectColumn::make('principal')
                    ->label('Principal')
                    ->options([
                        true => 'Sim',
                        false => 'Não',
                    ])
                    ->default(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('commands')
                        ->label(__('Comandos'))
                        ->icon('heroicon-c-numbered-list')
                        ->color('primary')
                        ->action(fn(Tables\Actions\Action $action, Bot $record) => $action->redirect(BotCommandResource::getUrl('index', ['bot_id' => $record->id]))),
                    Tables\Actions\EditAction::make()
                        ->color('info'),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger')
                ])
            ]);
    }

    public static function getWebhookUrl(string $token): string
    {
        $url = env('NOTIFY_BASE_URL');
        return "$url/telegram/$token/webhook";
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBots::route('/'),
            'create' => Pages\CreateBot::route('/create'),
            'edit' => Pages\EditBot::route('/{record}/edit'),
        ];
    }
}
