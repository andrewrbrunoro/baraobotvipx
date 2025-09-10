<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Repositories\CommandRepository;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Telegram\Bot\Commands\Command;

class HelpCommand extends Command
{
    use HelperCommand,
        AuthCommand;

    protected string $name = 'help';

    protected string $description = 'Listar todos os comandos disponíveis';

    public function handle(): void
    {
        $text = '';
        $type = $this->chatType();
        $owner = $this->isOwner();

        if ($this->isAppBot()) {
            $commands = CommandRepository::make()
                ->appCommands($type === 'supergroup', $owner);
        } else {
            $commands = CommandRepository::make()
                ->botCommands($this->getBot()->id, CommandRepository::$chatTypes[$type]);
        }

        foreach ($commands as $command) {
            if (!$owner && $command->only_owner)
                continue;

            if (str_contains($command->name, 'pix') || str_contains($command->name, 'credit_card'))
                continue;

            $text .= sprintf('/%s - %s' . PHP_EOL, $command->name, $command->description);
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
