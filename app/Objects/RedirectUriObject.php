<?php declare(strict_types=1);

namespace App\Objects;

use App\Models\RedirectUri;

class RedirectUriObject
{
    public function __construct(
        protected string $uri,
        protected int    $maxReadTimes = 0,
    )
    {
    }

    public static function make(string $uri, int $max_read_times = 0): self
    {
        return new self($uri, $max_read_times);
    }

    public function generate(): string
    {
        $result = RedirectUri::firstOrCreate([
            'code' => $this->getCode(),
        ], [
            'uri' => $this->getUri(),
            'read_times' => 0,
            'max_read_times' => $this->getMaxReadTimes(),
        ]);

        return route('shortUri', $result->code);
    }

    public function getCode($length = 6): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMaxReadTimes(): int
    {
        return $this->maxReadTimes;
    }
}
