<?php declare(strict_types=1);

namespace App\Services\Payments\PushinPay;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PixPushinPay
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public static function make(PaymentService $paymentService): self
    {
        return new self($paymentService);
    }

    public function run(Order $order): string
    {
        try {
            $pushinPayService = PushinPayService::make($this->paymentService);

            $type = app($order->item_type)
                ->find($order->item_id);

            $value = floatval($order->price_sale > 0 ? $order->price_sale : $order->price);

            $response = $pushinPayService->create(
                value: $value,
                webhookUrl: $this->getNotifyUrl(),
                splitRules: []
            );

            // Salvar o ID da transação no pedido
            if (isset($response['id'])) {
                $order->platform_id = $response['id'];
            }

            // Retornar o QR Code ou dados do PIX
            $qrCode = $response['qr_code'] ?? $response['pix_code'] ?? '';

            // Salvar QR Code base64 e caminho do arquivo se disponível
            if (isset($response['qr_code_base64']) && !empty($response['qr_code_base64'])) {
                $qrCodePath = $this->saveQrCodeBase64($response['qr_code_base64'], $qrCode);
                
                // Salvar no banco de dados
                $order->qrcode = $response['qr_code_base64'];
                $order->qrcode_path = $qrCodePath;
            }

            $order->save();

            return $qrCode;

        } catch (\Exception $e) {
            Log::error('PushinPay PIX error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid
            ]);
            
            throw new \Exception('Erro ao gerar PIX PushinPay: ' . $e->getMessage());
        }
    }

    public function getTransaction(string $transactionId): array
    {
        try {
            $pushinPayService = PushinPayService::make($this->paymentService);
            return $pushinPayService->get($transactionId);
        } catch (\Exception $e) {
            Log::error('PushinPay get transaction error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId
            ]);
            
            throw new \Exception('Erro ao buscar transação PushinPay: ' . $e->getMessage());
        }
    }

    public function getNotifyUrl(): string
    {
        return env('PUSHINPAY_NOTIFY_URL');
    }

    private function saveQrCodeBase64(string $base64, string $qrCode): string
    {
        // Remove o prefixo data:image/png;base64, se existir
        $base64Data = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64);
        
        $decode = base64_decode($base64Data);
        $path = md5($qrCode) . '.jpg';

        $saveTo = '/payments/pushinpay/pix/' . $path;

        Storage::disk('pix')->put($saveTo, $decode);

        return $saveTo;
    }
}
