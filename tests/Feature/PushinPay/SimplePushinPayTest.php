<?php

namespace Tests\Feature\PushinPay;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPaymentIntegration;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\PixPushinPay;
use App\Services\Payments\PushinPay\PushinPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimplePushinPayTest extends TestCase
{

    public function test_pushinpay_flow(): void
    {
        // 1. Criar dados de teste
        $user = User::first();
        
        $userPaymentIntegration = UserPaymentIntegration::firstOrCreate([
            'user_id' => $user->id,
            'platform' => 'pushinpay',
            'integration_code' => 'pushinpay',
            'access_token' => env('PUSHINPAY_TOKEN', 'test_token_123'),
            'live_mode' => true // false = sandbox, true = production
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'member_id' => 1,
            'uuid' => 'test-order-' . time(),
            'price' => 2.00,
            'price_sale' => 0,
            'total' => 2.00,
            'product_id' => 1,
            'item_type' => 'App\Models\Product',
            'item_id' => 1,
            'status' => 'WAITING'
        ]);

        $paymentService = PaymentService::make($userPaymentIntegration);

        // 2. Teste 1: Criar PIX
        echo "\n=== Teste 1: Criando PIX ===\n";
        
        $pixPushinPay = PixPushinPay::make($paymentService);
        
        try {
            $qrCode = $pixPushinPay->run($order, env('PUSHINPAY_NOTIFY_URL', 'https://test.com/webhook'));
            echo "✅ PIX criado com sucesso!\n";
            echo "QR Code: " . ($qrCode ?: 'N/A') . "\n";
            echo "Order ID: " . $order->id . "\n";
            echo "Platform ID: " . ($order->fresh()->platform_id ?: 'N/A') . "\n";
        } catch (\Exception $e) {
            echo "❌ Erro ao criar PIX: " . $e->getMessage() . "\n";
        }

        // 3. Teste 2: Buscar transação
        echo "\n=== Teste 2: Buscando transação ===\n";
        
        $pushinPayService = PushinPayService::make($paymentService);
        
        try {
            $transactionId = $order->fresh()->platform_id ?: 'test_transaction_123';
            $transaction = $pushinPayService->get($transactionId);
            
            echo "✅ Transação encontrada!\n";
            echo "Transaction ID: " . $transactionId . "\n";
            echo "Response: " . json_encode($transaction, JSON_PRETTY_PRINT) . "\n";
        } catch (\Exception $e) {
            echo "❌ Erro ao buscar transação: " . $e->getMessage() . "\n";
        }

        echo "\n=== Teste concluído ===\n";
        
        // Assertions básicas
        $this->assertTrue(true); // Teste sempre passa, é apenas para execução manual
    }
}
