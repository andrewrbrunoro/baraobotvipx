<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use App\Services\Messengers\Telegram\Keyboards\GroupKeyboard;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class HowToSetupGroup extends Command
{

    use AuthCommand,
        HelperCommand;

    protected string $name = 'how_to_setup_group';

    protected string $description = 'Como vincular um BOT ao grupo';

    public function handle(): void
    {
        $project = env('APP_NAME');

        $this->replyWithChatAction([
            'action' => Actions::TYPING,
        ]);

        if ($this->isAppBot()) {
            $this->replyWithMessage([
                'parse_mode' => 'HTML',
                'text' => <<<HTML
            Siga os passos abaixo para permitir que o BOT gerencie seu grupo:

            🔗 Acesse o grupo onde deseja que o BOT atue como administrador.
            ➕ Adicione o BOT como Administrador do grupo.
            No chat do grupo, 💬 digite o comando /setup_group.
            🎉 Pronto! Agora, acesse o painel do $project e gerencie o grupo de forma prática e centralizada.
            HTML
            ]);
        } else {
            $this->replyWithMessage([
                'parse_mode' => 'HTML',
                'text' => <<<HTML
                Siga os passos abaixo para permitir que o BOT gerencie seu grupo:

                🔗 Acesse o grupo onde deseja que o BOT atue como administrador.
                ➕ Adicione o BOT como Administrador do grupo.
                No chat do grupo, 💬 digite o comando /setup_group.
                🎉 Pronto! Agora, acesse o painel do $project e gerencie o grupo de forma prática e centralizada.

                Para facilitar, clique no botão abaixo:
                HTML,
                'reply_markup' => GroupKeyboard::selectGroup($this->getBot()->username)
            ]);
        }
    }

    public function getOnlyOwner(): bool
    {
        return true;
    }
}
