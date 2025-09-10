<?php declare(strict_types=1);

namespace App\Services\Payments\MercadoPago\Enums;

use App\Enums\PaymentStatusEnum;

enum MercadoPaymentEnum: string
{

    const APPROVED = 'approved';

    const FAILURE = 'failure';

    const PENDING = 'pending';

    public static function status(string $status): string
    {
        return match ($status) {
            self::APPROVED => PaymentStatusEnum::SUCCESS,
            self::PENDING => PaymentStatusEnum::WAITING,
            default => PaymentStatusEnum::FAILURE,
        };
    }

}
