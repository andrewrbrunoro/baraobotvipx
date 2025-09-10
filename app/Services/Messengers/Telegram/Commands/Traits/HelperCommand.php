<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands\Traits;

use App\Models\Bot;
use App\Models\Member;
use App\Repositories\BotRepository;
use Illuminate\Support\Collection;

trait HelperCommand
{
    public function getMember(): ?Member
    {
        $from = $this->getFrom()->toArray();

        if ($from['is_bot'] === true)
            $from = $this->getChat()->toArray();

        return Member::where('code', $from['id'])
            ->first();
    }

    public function chatType(): string
    {
        if ($this->getUpdate()->isType('channel_post'))
            return 'group';

        return $this->getUpdate()
            ->getChat()
            ->type;
    }

    public function getBot(): ?Bot
    {
        return (new BotRepository())
            ->findByToken($this->getTelegram()->getAccessToken());
    }

    public function customMessage(): Collection
    {
        if ($this->getUpdate()->isType('channel_post')) {
            return $this->getUpdate()->channelPost;
        } else {
            return $this->getUpdate()->getMessage();
        }
    }

    public function getChat(): Collection
    {
        return $this->customMessage()->chat ?? collect();
    }

    public function getFrom(): Collection
    {
        return $this->customMessage()->from ?? collect();
    }

    public function getChatId(): int|string
    {
        return $this->getChat()->id;
    }

    public function getUserId(): int|string
    {
        if (!$this->getUpdate()->isType('channel_post'))
            return $this->getFrom()->id;

        $bot = $this->getBot();
        return $bot->owner_code;
    }
}
