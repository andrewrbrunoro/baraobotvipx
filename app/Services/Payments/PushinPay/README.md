# PushinPay Service

Service para integração com a API do PushinPay para pagamentos PIX.

## Estrutura

```
app/Services/Payments/PushinPay/
├── PushinPayService.php      # Service principal com métodos create e get
├── PixPushinPay.php          # Classe específica para PIX
├── Enums/
│   └── PushinPayEnvironment.php  # Enum para ambientes (production/sandbox)
└── Exceptions/
    └── PushinPayException.php     # Exceção específica do PushinPay
```

## Uso

### Criar pagamento PIX

```php
use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\PixPushinPay;
use App\Models\Order;

// Criar instância do PaymentService
$paymentService = PaymentService::make($userPaymentIntegration);

// Criar instância do PixPushinPay
$pixPushinPay = PixPushinPay::make($paymentService);

// Gerar PIX para um pedido
$qrCode = $pixPushinPay->run($order, env('PUSHINPAY_NOTIFY_URL'));
```

### Buscar transação

```php
use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\PushinPayService;

// Criar instância do PaymentService
$paymentService = PaymentService::make($userPaymentIntegration);

// Criar instância do PushinPayService
$pushinPayService = PushinPayService::make($paymentService);

// Buscar transação por ID
$transaction = $pushinPayService->get($transactionId);
```

### Usar diretamente o service principal

```php
use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\PushinPayService;

$paymentService = PaymentService::make($userPaymentIntegration);
$pushinPayService = PushinPayService::make($paymentService);

// Criar pagamento
$response = $pushinPayService->create(
    value: 100.50,
    webhookUrl: env('PUSHINPAY_NOTIFY_URL'),
    splitRules: []
);

// Buscar transação
$transaction = $pushinPayService->get('transaction_id');
```

## Configuração

### Variáveis de Ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
PUSHINPAY_TOKEN=your_pushinpay_access_token_here
PUSHINPAY_NOTIFY_URL=https://your-domain.com/pushinpay/webhook
PUSHINPAY_BARAOBOT_CLIENT_ID=your_baraobot_client_id_here  # Obrigatório - sempre enviado nas requisições
```

### Configurações do UserPaymentIntegration

O service utiliza as seguintes configurações do `UserPaymentIntegration`:

- `access_token`: Token de acesso da API (usa `PUSHINPAY_TOKEN` se disponível)
- `live_mode`: Ambiente (true = production, false = sandbox)
- `platform`: Nome da plataforma ('pushinpay')
- `integration_code`: Código da integração ('pushinpay')

## URLs

- **Produção**: https://api.pushinpay.com.br
- **Sandbox**: https://api-sandbox.pushinpay.com.br

## Split Rules

O service **sempre** inclui o `PUSHINPAY_BARAOBOT_CLIENT_ID` nas regras de split por padrão:

```php
// Exemplo de split rules
$splitRules = [
    [
        'client_id' => 'client_123',
        'value' => 80.00, // Valor fixo
        'type' => 'fixed'
    ]
];

// O service sempre adiciona o BaraoBot por padrão
$response = $pushinPayService->create(100.00, $webhookUrl, $splitRules);
```

### Estrutura do Split Rule

```php
[
    'client_id' => 'ID do cliente',
    'value' => 100.00, // Valor fixo em reais
    'type' => 'fixed' // Tipo: 'fixed' (valor fixo)
]
```
