<?php

namespace App\Services\Messengers\Telegram\Commands\Traits;

use App\Models\Order;

trait AuthCommand
{

    public function isAppBot(): bool
    {
        return $this->getBot()->token === env('APP_BOT_TELEGRAM_TOKEN');
    }

    public function getOrder(): ?Order
    {
        $member = $this->getMember();

        return Order::where('member_id')
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->first();
    }

    public function isOwner(): bool
    {
        $userId = $this->getUserId();

        if (!$userId)
            return false;

        $bot = $this->getBot();

        if (!$bot)
            return false;

        return (string)$userId === (string)$bot->owner_code;
    }

}
