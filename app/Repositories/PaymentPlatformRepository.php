<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\PaymentPlatform;

class PaymentPlatformRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(PaymentPlatform::class);
    }

    public static function make(): self
    {
        return new self();
    }

}
