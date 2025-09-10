<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

use Telegram\Bot\Api;

class WebhookTelegram
{

    public function __construct(
        protected Api $telegram,
    )
    {
    }

    public static function make(Api $telegram): self
    {
        return new self($telegram);
    }

    public function delete(): self
    {
        $this->telegram->deleteWebhook();

        return $this;
    }

    public function set(string $url): self
    {
        $result = $this->telegram->setWebhook(['url' => $url]);

        dump($result, $url);

        return $this;
    }

    public function manageCommand(): CommandTelegram
    {
        return CommandTelegram::make($this->telegram, null);
    }
}
