<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(User::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function alreadyHaveTelegramAccount(int|string $telegram_owner_code): ?User
    {
        return $this->db
            ->where('telegram_owner_code', $telegram_owner_code)
            ->first();
    }

    public function hasTelegramOwnerCode(): bool
    {
        $user = auth()->user();

        return !empty($user->telegram_owner_code);
    }

}
