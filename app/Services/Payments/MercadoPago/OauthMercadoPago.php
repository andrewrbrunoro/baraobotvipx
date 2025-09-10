<?php declare(strict_types=1);

namespace App\Services\Payments\MercadoPago;

use App\Models\UserPaymentIntegration;
use App\Services\Payments\PaymentService;
use MercadoPago\Client\OAuth\OAuthClient;
use MercadoPago\Client\OAuth\OAuthCreateRequest;
use MercadoPago\Exceptions\MPApiException;

class OauthMercadoPago
{
    public function __construct(
        protected ?PaymentService $paymentService,
    )
    {
    }

    public static function make(?PaymentService $payment_service = null): self
    {
        return new self($payment_service);
    }

    public function oauth2Url(string $state_id): string
    {
        return __(
            'https://auth.mercadopago.com/authorization?client_id=:client_id&response_type=code&platform_id=mp&state=:state_id&redirect_uri=:redirect_url',
            [
                'client_id' => $this->getClientId(),
                'redirect_url' => $this->getBaseRedirectUrl(),
                'state_id' => $state_id,
            ]
        );
    }

    public function setup(string $code, string $hash): ?UserPaymentIntegration
    {
        try {

            $userId = decrypt($hash);

            $client = new OAuthClient();
            $request = new OAuthCreateRequest();
            $request->client_secret = $this->getClientSecret();
            $request->client_id = $this->getClientId();
            $request->code = $code;
            $request->redirect_uri = $this->getBaseRedirectUrl();

            $oauth = $client->create($request);

            $expiresIn = $oauth->expires_in;

            return UserPaymentIntegration::updateOrCreate([
                'user_id' => $userId,
                'platform' => 'MERCADO_PAGO',
            ], [
                'integration_code' => $oauth->user_id,
                'token_type' => $oauth->token_type,
                'scope' => $oauth->scope,
                'access_token' => $oauth->access_token,
                'refresh_token' => $oauth->refresh_token ?? null,
                'public_key' => $oauth->public_key,
                'expire_in' => $expiresIn,
                'expire_at' => now()->addSeconds($expiresIn)
            ]);
        } catch (MPApiException $e) {
            return null;
        }
    }

    public function getPublicKey(): string
    {
        return env('MERCADOPAGO_PUBLIC_KEY');
    }

    public function getAccessToken(): string
    {
        return env('MERCADOPAGO_ACCESS_TOKEN');
    }

    public function getClientId(): string|int
    {
        return env('MERCADOPAGO_CLIENT_ID');
    }

    public function getClientSecret(): string
    {
        return env('MERCADOPAGO_CLIENT_SECRET');
    }

    public function getBaseRedirectUrl(): string
    {
        return sprintf(
            '%s/mercado-pago',
            env('PAYMENT_REDIRECT_URL')
        );
    }

    public function getPaymentService(): ?PaymentService
    {
        return $this->paymentService;
    }
}
