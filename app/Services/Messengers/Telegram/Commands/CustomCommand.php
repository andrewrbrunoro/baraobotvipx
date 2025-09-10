<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Models\Command as CommandModel;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Telegram\Bot\Commands\Command;

class CustomCommand extends Command
{
    use AuthCommand,
        HelperCommand;

    public function __construct(
        protected CommandModel $command
    )
    {
        $this->name = $this->command->name;
        $this->description = $this->command->description;

        $this->setDescription($this->command->description);
        $this->setName($this->command->name);
    }

    public static function init(CommandModel $command): self
    {
        return new self($command);
    }

    public function getTelegramData(): array
    {
        $data = [];
        foreach ($this->getUpdate()->getMessage()->from as $key => $value) {
            $data['telegram_' . $key] = $value;
        }

        return $data;
    }

    public function handle(): void
    {
        if (!$this->isOwner())
            return;

        $params = unserialize($this->command->parameters);
        $text = __($params->getText() ?? '', [
            ...$this->getTelegramData(),
            'company' => env('APP_NAME'),
        ]);

        $this->replyWithMessage([
            ...$params->getData(),
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    public function getDescription(): string
    {
        return $this->command->description;
    }

    public function getName(): string
    {
        return $this->command->name;
    }

    public function getOnlyOwner(): bool
    {
        return (bool) $this->command->only_owner;
    }
}
