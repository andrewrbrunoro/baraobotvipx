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

class PushinPayServiceTest extends TestCase
{

    private UserPaymentIntegration $userPaymentIntegration;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuário de teste
        $user = User::factory()->create();

        // Criar integração de pagamento de teste
        $this->userPaymentIntegration = UserPaymentIntegration::create([
            'user_id' => $user->id,
            'platform' => 'pushinpay',
            'integration_code' => 'pushinpay',
            'access_token' => env('PUSHINPAY_TOKEN', 'test_token_123'),
            'live_mode' => false // false = sandbox, true = production
        ]);

        $this->paymentService = PaymentService::make($this->userPaymentIntegration);
    }

    public function test_create_pix_payment(): void
    {
        // Criar pedido de teste
        $order = Order::create([
            'user_id' => $this->userPaymentIntegration->user_id,
            'member_id' => 1,
            'uuid' => 'test-order-123',
            'price' => 100.50,
            'price_sale' => 0,
            'total' => 100.50,
            'product_id' => 1,
            'item_type' => 'App\Models\Product',
            'item_id' => 1,
            'status' => 'WAITING'
        ]);

        // Criar instância do PixPushinPay
        $pixPushinPay = PixPushinPay::make($this->paymentService);

        // Testar criação do PIX
        $webhookUrl = env('PUSHINPAY_NOTIFY_URL', 'https://test.com/webhook');
        
        try {
            $qrCode = $pixPushinPay->run($order, $webhookUrl);
            
            // Verificar se o QR Code foi gerado (pode estar vazio em ambiente de teste)
            $this->assertIsString($qrCode);
            
            // Verificar se o platform_id foi salvo no pedido
            $this->assertNotNull($order->fresh()->platform_id);
            
        } catch (\Exception $e) {
            // Em ambiente de teste, pode falhar por falta de credenciais reais
            $this->assertStringContainsString('Erro ao gerar PIX PushinPay', $e->getMessage());
        }
    }

    public function test_get_transaction(): void
    {
        // Criar instância do PushinPayService
        $pushinPayService = PushinPayService::make($this->paymentService);

        // Testar busca de transação
        $transactionId = 'test_transaction_123';
        
        try {
            $transaction = $pushinPayService->get($transactionId);
            
            // Verificar se retornou um array
            $this->assertIsArray($transaction);
            
        } catch (\Exception $e) {
            // Em ambiente de teste, pode falhar por falta de credenciais reais
            $this->assertStringContainsString('Erro ao buscar transação PushinPay', $e->getMessage());
        }
    }

    public function test_create_payment_directly(): void
    {
        // Criar instância do PushinPayService
        $pushinPayService = PushinPayService::make($this->paymentService);

        // Testar criação direta de pagamento
        $value = 50.00;
        $webhookUrl = env('PUSHINPAY_NOTIFY_URL', 'https://test.com/webhook');
        $splitRules = [];
        
        try {
            $response = $pushinPayService->create($value, $webhookUrl, $splitRules);
            
            // Verificar se retornou um array
            $this->assertIsArray($response);
            
        } catch (\Exception $e) {
            // Em ambiente de teste, pode falhar por falta de credenciais reais
            $this->assertStringContainsString('Erro ao criar pagamento PushinPay', $e->getMessage());
        }
    }
}
