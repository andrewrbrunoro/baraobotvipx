<?php declare(strict_types=1);

namespace App\Services\Payments\PushinPay\Enums;

enum PushinPayEnvironment: string
{
    case PRODUCTION = 'production';
    case SANDBOX = 'sandbox';

    public function getBaseUrl(): string
    {
        return match($this) {
            self::PRODUCTION => 'https://api.pushinpay.com.br',
            self::SANDBOX => 'https://api-sandbox.pushinpay.com.br',
        };
    }
}
