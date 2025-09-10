<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserPaymentIntegration;

class UserPaymentIntegrationRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(UserPaymentIntegration::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function hasIntegration(int $user_id): bool
    {
        return $this
            ->db
            ->where('user_id', $user_id)
            ->exists();
    }

}
