<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\BotCommand;

class BotCommandRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(BotCommand::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function description(int $bot_id, int|string $command_id): string
    {
        $command = CommandRepository::make()
            ->byNameOrId(
                $command_id
            );
        if (!$command)
            return '';

        $botCommand = $this->db
            ->where('bot_id', $bot_id)
            ->where('command_id', $command->id)
            ->first();

        if (!$botCommand || empty($botCommand->description))
            return $command->description;

        return $botCommand->description;
    }

}
