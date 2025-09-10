<?php declare(strict_types=1);

namespace App\Services\Messengers;

use App\Models\AuthPin;

class ApproveBot
{
    public string $encrypt;

    public function __construct(
        protected AuthPin $authPin,
        protected int|string $owner_id,
    )
    {
        $this->encrypt = encrypt($this);
    }

    public static function make(AuthPin $auth_pin, int|string $owner_id): self
    {
        return new self($auth_pin, $owner_id);
    }

    public function getAuthPin(): AuthPin
    {
        return $this->authPin;
    }

    public function getOwnerId(): int|string
    {
        return $this->owner_id;
    }
}
