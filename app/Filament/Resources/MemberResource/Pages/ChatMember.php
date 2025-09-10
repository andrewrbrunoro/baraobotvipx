<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Bot;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Models\BotMember;
use App\Services\Messengers\Telegram\ChatTelegramManager;
class ChatMember extends Page
{
    protected static string $resource = MemberResource::class;

    protected static string $view = 'filament.resources.member-resource.pages.chat-member';

    public ?array $data = [];

    public $record;

    public function mount($record): void
    {
        $this->record = $this->getResource()::getModel()::find($record);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $botIds = BotMember::where('member_id', $this->record->id)->pluck('bot_id');

        return $form
            ->schema([
                Select::make('bot_id')
                    ->label(__('Selecione o Bot'))
                    ->options(Bot::whereIn('id', $botIds)->pluck('first_name', 'id'))
                    ->required(),
                TextInput::make('message')
                    ->label(__('Mensagem'))
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label(__('Enviar Mensagem'))
                ->action(function (): void {
                    $data = $this->form->getState();

                    $bot = $this->record->botMembers->where('bot_id', $data['bot_id'])->first();

                    if (!$bot) {
                        $this->notify('error', 'Bot não encontrado para este usuário');
                        return;
                    }

                    // Aqui você implementaria a lógica de envio para o Telegram
                    // Exemplo:
                    // Http::post("https://api.telegram.org/bot{$bot->bot->token}/sendMessage", [
                    //     'chat_id' => $this->record->telegram_id,
                    //     'text' => $data['message'],
                    // ]);

                    $chatTelegram = ChatTelegramManager::make()
                        ->setBot($bot->bot);

                    $chatTelegram->sendMessage($this->record->code, $data['message']);

                    // Salvar no log
                    // $this->record->memberLogs()->create([
                    //     'message' => $data['message'],
                    //     'direction' => 'out',
                    //     'bot_id' => $data['bot_id'],
                    // ]);

                    $this->form->fill();
                }),
        ];
    }
}
