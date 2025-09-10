<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;

class AppHelpCommand extends Command
{
    use HelperCommand,
        AuthCommand;

    protected string $name = 'help';

    protected string $description = 'Listar todos os comandos disponíveis';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $commands = $this->getCommandBus()->getCommands();

        $text = '';
        foreach ($commands as $command) {
            $text .= sprintf('/%s - %s' . PHP_EOL, $command->getName(), $command->getDescription());
        }

        $this->replyWithMessage([
            'text' => $text
        ]);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}
