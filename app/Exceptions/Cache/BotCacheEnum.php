<?php declare(strict_types=1);

namespace App\Exceptions\Cache;

enum BotCacheEnum: string
{

    const FREE_COMMANDS = 'FREE_COMMANDS';

    public static function bot(string $token): string
    {
        return sprintf('bot_%s', $token);
    }

}

