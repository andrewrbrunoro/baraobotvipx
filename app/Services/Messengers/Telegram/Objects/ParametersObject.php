<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Objects;

use Telegram\Bot\Keyboard\Keyboard;

class ParametersObject
{
    public function __construct(
        public array $data = [],
    )
    {
    }

    public static function make(array $data = []): self
    {
        return new self($data);
    }

    public function setReplyMarkup(Keyboard $keyboard = null): self
    {
        if (!$keyboard)
            $keyboard = Keyboard::remove(['selective' => false]);

        $this->data['reply_markup'] = $keyboard;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->data['text'] = $text;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getReplyMarkup(): ?Keyboard
    {
        return $this->data['reply_markup'] ?? null;
    }

    public function getText(): string
    {
        return $this->data['text'] ?? '';
    }
}
