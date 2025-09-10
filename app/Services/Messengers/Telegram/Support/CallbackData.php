<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

class CallbackData
{
    protected array $data = [];

    public function __construct(
        protected string $command,
    )
    {
    }

    public static function make(string $command): self
    {
        return new self($command);
    }

    public function mergeData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function setData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'data' => sprintf('/%s', $this->command),
            'payload' => $this->data,
        ];
    }

    public function get(): string
    {
        $encode = json_encode($this->toArray());
        if (mb_strlen($encode, '8bit') > 64)
            throw new \Exception('CallbackData é muito longo, no máximo 64 bytes');

        return $encode;
    }

}
