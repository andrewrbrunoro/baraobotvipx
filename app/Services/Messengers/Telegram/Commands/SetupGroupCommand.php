<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Repositories\ChatRepository;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class SetupGroupCommand extends Command
{
    use HelperCommand,
        AuthCommand;

    protected string $name = 'setup_group';

    protected string $description = 'Vincular o BOT ao grupo';

    public function sendToOwner(array $params): void
    {
        $this->getTelegram()
            ->sendMessage([
                'chat_id' => $this->getBot()->owner_code,
                ...$params
            ]);
    }

    public function handle(): void
    {
        $chatCode = $this->getChatId();
        $chatName = $this->getChat()->title ?? null;

        $canSetup = ChatRepository::make()
            ->canSetup($chatCode);

        if ($this->chatType() === 'private') {
            $this->sendToOwner([
                'text' => __(<<<HTML
                Você não pode registar um CHAT privado, use esse comando apenas em um grupo.
                HTML),
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->setSelective(true)
                    ->row([
                        Button::make()
                            ->setText(__('Selecione um GRUPO'))
                            ->setUrl(sprintf('https://t.me/%s?startgroup=start', $this->getBot()->username))
                    ]),
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        if (!$canSetup) {
            $this->sendToOwner([
                'text' => __(<<<HTML
                Esse GRUPO já está registrado.

                Acesse o painel e administre o GRUPO por lá.
                HTML),
                'reply_markup' => null,
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        $result = ChatRepository::make()
            ->setup(
                chat_params: [
                    'chat_code' => $chatCode,
                    'chat_name' => $chatName,
                ],
                bot: $this->getBot(),
                verified_by: $this->getUserId(),
            );

        if (!$result) {
            $this->sendToOwner([
                'text' => __('Não foi possível vincular o BOT com o Grupo, tente novamente mais tarde!'),
                'reply_markup' => null,
            ]);
        } else {
            $this->sendToOwner([
                'text' => __(<<<HTML
                🎉 Chat vinculado com sucesso! 🎉

                Agora você pode acessar seu painel e:

                💡 Obter insights sobre o chat
                👥 Gerenciar os membros
                💸 Definir preço para acesso
                ...e muito mais!

                Aproveite ao máximo as funcionalidades! 🚀
                HTML),
                'parse_mode' => 'HTML',
            ]);
        }
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}
