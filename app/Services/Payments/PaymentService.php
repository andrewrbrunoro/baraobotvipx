<?php declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\User;
use App\Models\UserPaymentIntegration;

class PaymentService implements PaymentInterface
{
    public function __construct(
        protected UserPaymentIntegration $user_payment
    )
    {
    }

    public static function make(UserPaymentIntegration $user_payment): self
    {
        return new self($user_payment);
    }

    public function getUser(): User
    {
        return User::find($this->user_payment->user_id);
    }

    public function getUserPayment(): UserPaymentIntegration
    {
        return $this->user_payment;
    }

    public function getAccessToken(): string
    {
        return $this->getUserPayment()->access_token;
    }

    public function getIntegrationCode(): string
    {
        return $this->getUserPayment()->integration_code;
    }
}
