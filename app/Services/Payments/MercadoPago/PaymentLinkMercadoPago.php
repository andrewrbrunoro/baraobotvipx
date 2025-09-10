<?php declare(strict_types=1);

namespace App\Services\Payments\MercadoPago;

use App\Models\Order;
use App\MarketplaceManager;
use App\Services\Payments\PaymentLinkInterface;
use App\Services\Payments\PaymentService;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment\Payer;
use MercadoPago\Resources\Preference\Item;

class PaymentLinkMercadoPago implements PaymentLinkInterface
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public static function make(PaymentService $paymentService): self
    {
        return new self($paymentService);
    }

    public function run(Order $order, ?Payer $payer = null): string
    {
        try {
            MercadoPagoConfig::setAccessToken(
                $this->paymentService->getAccessToken()
            );

            $preferenceClient = new PreferenceClient();

            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders([
                'X-Idempotency-Key' => $order->id,
            ]);

            // TEMPORARIO
            $type = app($order->item_type)
                ->find($order->item_id);
            // TEMPORARIO

            $item = new Item();
            $item->id = (string) $order->product_id;
            $item->title = $type->name ?? $type->title;
            $item->quantity = $order->quantity ?? 1;
            $item->unit_price = floatval($order->price_sale > 0 ? $order->price_sale : $order->price);
            $item->currency_id = 'BRL';

            $items = [$item];

            $backUrls = [
                'success' => sprintf('%s/mercado-pago/notify/success', env('PAYMENT_NOTIFY_URL')),
                'failure' => sprintf('%s/mercado-pago/notify/failure', env('PAYMENT_NOTIFY_URL')),
                'pending' => sprintf('%s/mercado-pago/notify/pending', env('PAYMENT_NOTIFY_URL')),
            ];

            $marketplaceManager = new MarketplaceManager();

            $preference = $preferenceClient->create([
                'payer' => $payer,
                'back_urls'=> $backUrls,
                'expires' => false,
                'items' => $items,
//                'marketplace_fee' => $marketplaceManager->marketplaceFee($item->unit_price),
                'auto_return' => 'approved',
                'binary_mode' => true,
                'external_reference' => $order->uuid,
                'notification_url' => $this->getNotifyUrl(),
                'payment_methods' => array(
                    'default_payment_method_id' => 'master',
                    'excluded_payment_types' => array(
                        array(
                            'id' => 'ticket'
                        )
                    ),
                ),
                'statement_descriptor' => sprintf('Pagamento efetuado na %s', env('APP_NAME')),
            ]);

            $order->platform_id = $preference->id;

            return $preference->init_point;
        } catch (MPApiException $e) {
            info('erro mercado pago: ');
            info($e->getTraceAsString());
            info(print_r($e->getApiResponse(), true));
            return '';
        }
    }

    public function getNotifyUrl(): string
    {
        return sprintf('%s/mercado-pago/notify', env('PAYMENT_NOTIFY_URL'));
    }
}
