<?php declare(strict_types=1);

namespace App\Exceptions\Cache;

enum CommandMessageEnum
{
    public static function message(int $user_id, string $key, string $command): string
    {
        return sprintf('command_message_%s_%s_%s', $user_id, $key, $command);
    }
}
