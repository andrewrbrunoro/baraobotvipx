<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\TelegramVideo;

class TelegramVideoRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(TelegramVideo::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function findByBotAndName(string $botToken, string $name): ?TelegramVideo
    {
        return $this->db
            ->where('name', $name)
            ->where('bot_token', $botToken)
            ->first();
    }
}
