<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

use Telegram\Bot\Api;

class UserTelegram
{

    public function __construct(
        protected string $userID,
        protected Api    $telegram,
    )
    {
    }

    public static function make(
        string $user_id,
        string $bot_token
    ): self
    {
        return new self(
            userID: $user_id,
            telegram: new Api($bot_token),
        );
    }

    public function getUserID(): string
    {
        return $this->userID;
    }
}
