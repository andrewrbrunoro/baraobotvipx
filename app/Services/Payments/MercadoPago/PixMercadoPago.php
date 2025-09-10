<?php declare(strict_types=1);

namespace App\Services\Payments\MercadoPago;

use App\MarketplaceManager;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment\Payer;
use MercadoPago\Resources\Preference\Item;

class PixMercadoPago
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

            $paymentClient = new PaymentClient();

            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders([
                'X-Idempotency-Key ' . $order->id,
            ]);

            // TEMPORARIO
            $type = app($order->item_type)
                ->find($order->item_id);
            // TEMPORARIO
            $item = new Item();
            $item->id = (string)$order->product_id;
            $item->title = $type->name ?? $type->title;
            $item->quantity = $order->quantity ?? 1;
            $item->unit_price = floatval($order->price_sale > 0 ? $order->price_sale : $order->price);
//            $item->currency_id = 'BRL';

            $items = [$item];

            $marketplaceManager = new MarketplaceManager();

            $payment = $paymentClient->create([
                'additional_info' => [
                    'items' => $items,
                ],
//                'application_fee' => $marketplaceManager->marketplaceFee($item->unit_price),
                'payer' => [
                    'email' => 'teste@example.com',
                ],
                'binary_mode' => true,
                'external_reference' => $order->uuid,
                'installments' => 1,
                'payment_method_id' => 'pix',
                'transaction_amount' => $item->unit_price,
                'notification_url' => $this->getNotifyUrl(),
                'description' => sprintf('Pagamento efetuado na %s', env('APP_NAME')),
            ], $requestOptions);

            $order->platform_id = $payment->id;

            $transactionData = $payment->point_of_interaction->transaction_data;

            $qrCodeStr = $transactionData->qr_code;
            $qrCodeBase64 = $transactionData->qr_code_base64;

            $qrCodePath = self::saveQrCodeBase64($qrCodeBase64, md5($qrCodeStr));

            // Salvar no banco de dados
            $order->qrcode = $qrCodeBase64;
            $order->qrcode_path = $qrCodePath;
            $order->save();

            return $qrCodeStr;
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

    private static function saveQrCodeBase64(string $base64, string $name): string
    {
        $decode = base64_decode($base64);
        $path = $name . '.jpg';

        $saveTo = '/payments/mercado-pago/pix/' . $path;

        Storage::disk('pix')->put($saveTo, $decode);

        return $saveTo;
    }

}
