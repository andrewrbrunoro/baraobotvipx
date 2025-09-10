<?php

namespace App\Services\Payments;

use App\Models\User;
use App\Models\UserPaymentIntegration;

interface PaymentInterface
{
    public function __construct(
        UserPaymentIntegration $userPayment
    );

    public static function make(UserPaymentIntegration $user_payment): self;

    public function getUser(): User;

    public function getUserPayment(): UserPaymentIntegration;

    public function getAccessToken(): string;

    public function getIntegrationCode(): string;
}
