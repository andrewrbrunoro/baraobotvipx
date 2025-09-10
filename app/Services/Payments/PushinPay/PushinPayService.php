<?php declare(strict_types=1);

namespace App\Services\Payments\PushinPay;

use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\Enums\PushinPayEnvironment;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class PushinPayService
{
    private Client $client;
    private string $baseUrl;
    private string $accessToken;

    public function __construct(protected PaymentService $paymentService)
    {
        $this->accessToken = $this->paymentService->getAccessToken();
        $this->baseUrl = $this->getBaseUrl();
        $this->client = new Client();
    }

    public static function make(PaymentService $paymentService): self
    {
        return new self($paymentService);
    }

    public function create(float $value, string $webhookUrl, array $splitRules = []): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            // Converter valor para centavos
            $valueInCents = (int) ($value * 100);

            // Sempre incluir split rules se PUSHINPAY_BARAOBOT_CLIENT_ID estiver configurado
            $finalSplitRules = $this->getSplitRules($splitRules);

            $body = json_encode([
                'value' => $valueInCents,
                'webhook_url' => $webhookUrl,
                'split_rules' => $finalSplitRules
            ]);
            
            $request = new Request('POST', $this->baseUrl . '/api/pix/cashIn', $headers, $body);
            $response = $this->client->sendAsync($request)->wait();

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('PushinPay create error: ' . $e->getMessage(), [
                'value' => $value,
                'webhook_url' => $webhookUrl,
                'split_rules' => $splitRules
            ]);
            
            throw new \Exception('Erro ao criar pagamento PushinPay: ' . $e->getMessage());
        }
    }

    public function get(string $transactionId): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $request = new Request('GET', $this->baseUrl . '/api/transactions/' . $transactionId, $headers);
            $response = $this->client->sendAsync($request)->wait();

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('PushinPay get error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId
            ]);
            
            throw new \Exception('Erro ao buscar transação PushinPay: ' . $e->getMessage());
        }
    }

    private function getBaseUrl(): string
    {
        return PushinPayEnvironment::PRODUCTION->getBaseUrl();

        $liveMode = $this->paymentService->getUserPayment()->live_mode ?? true;
        
        return $liveMode 
            ? PushinPayEnvironment::PRODUCTION->getBaseUrl()
            : PushinPayEnvironment::SANDBOX->getBaseUrl();
    }

    private function getSplitRules(array $splitRules = []): array
    {
        $baraobotClientId = env('PUSHINPAY_BARAOBOT_CLIENT_ID', 'baraobot_default');
        
        // Converter valores das split rules para centavos
        foreach ($splitRules as &$rule) {
            if (isset($rule['value'])) {
                $rule['value'] = (int) (floatval($rule['value']) * 100);
            }
        }
        
        // Adicionar regra do BaraoBot se não existir
        $baraobotRuleExists = false;
        foreach ($splitRules as $rule) {
            if (isset($rule['client_id']) && $rule['client_id'] === $baraobotClientId) {
                $baraobotRuleExists = true;
                break;
            }
        }

        if (!$baraobotRuleExists) {
            $taxValue = env('TAX', 0);
            $splitRules[] = [
                'account_id' => $baraobotClientId,
                'value' => (int) (floatval($taxValue) * 100)
            ];
        }

        return $splitRules;
    }
}
