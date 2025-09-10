<?php declare(strict_types=1);

namespace App\Objects;

use App\Enums\PaymentPlatformEnum;
use App\Exceptions\Objects\OrderObjectException;
use App\Models\Bot;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Product;
use App\Models\UserPaymentIntegration;
use App\Services\Payments\MercadoPago\PaymentLinkMercadoPago;
use App\Services\Payments\MercadoPago\PixMercadoPago;
use App\Services\Payments\PushinPay\PixPushinPay;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderObject
{

    protected ?Order $order = null;

    protected ?Product $product = null;

    protected float $total = 0.0;

    protected bool $isGrid = false;

    public function __construct(
        protected int                    $user_id,
        protected Bot                    $bot,
        protected Member                 $member,
        protected UserPaymentIntegration $userPaymentIntegration,
    )
    {
    }

    public static function make(int $user_id, Bot $bot, Member $member, UserPaymentIntegration $user_payment_integration): self
    {
        return new self($user_id, $bot, $member, $user_payment_integration);
    }

    public function validate(): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make([
            'user_id' => $this->getUserId(),
            'member_id' => $this->getMember()->id,
            'bot_id' => $this->getBot()->id,
        ], [
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'member_id' => [
                'required',
                'exists:members,id',
            ],
            'bot_id' => [
                'required',
                'exists:bots,id'
            ]
        ]);
    }


    public function purchase(string $type = 'pix'): ?Order
    {
        $validate = $this->validate();
        if ($validate->fails())
            throw new OrderObjectException($validate->errors()->first(), 422);

        if ($this->order instanceof Order)
            return $this->order;

        $pixCode = null;
        $paymentLink = null;

        $modelType = $this->getProduct()->model_type;
        $modelId = $this->getProduct()->model_id;
        if ($this->getIsGrid()) {
            $modelType = $this->getParent()->model_type;
            $modelId = $this->getParent()->model_id;
        }

        $orderData = [
            'uuid' => Str::uuid()->toString(),
            'user_id' => $this->getUserId(),
            'member_id' => $this->getMember()->id,
            'bot_id' => $this->getBot()->id,
            'product_id' => $this->getProduct()->id,
            'item_type' => $modelType,
            'item_id' => $modelId,
            'price' => $this->getProduct()->price,
            'price_sale' => $this->getProduct()->price_sale,
            'total' => $this->getTotal(),
            'platform' => $this->getUserPaymentIntegration()->platform,
            'type' => $this->getProduct()->type,
        ];

        $this->order = new Order($orderData);

        if ($type === 'pix')
            $pixCode = $this->pix();
        else {
            $paymentLink = $this->paymentLink();
        }

        $this->order->pix_code = $pixCode;
        $this->order->payment_link = $paymentLink;

        $this->order->save();

        return $this->order->refresh();
    }

    public function paymentLink(): string
    {
        $paymentIntegration = $this->getUserPaymentIntegration();

        $paymentService = PaymentService::make($this->getUserPaymentIntegration());

        return match ($paymentIntegration->platform) {
            PaymentPlatformEnum::MERCADO_PAGO => PaymentLinkMercadoPago::make($paymentService)
                ->run(
                    order: $this->getOrder(),
                ),
            // Adicionar outros links de pagamento
            default => throw new OrderObjectException('Plataforma não encontrada.'),
        };
    }

    public function pix(): string
    {
        $paymentIntegration = $this->getUserPaymentIntegration();

        $paymentService = PaymentService::make($this->getUserPaymentIntegration());

        return match ($paymentIntegration->platform) {
            PaymentPlatformEnum::MERCADO_PAGO => PixMercadoPago::make($paymentService)
                ->run(
                    order: $this->order,
                ),
            PaymentPlatformEnum::PUSHINPAY => PixPushinPay::make($paymentService)
                ->run(
                    order: $this->order,
                ),
            default => throw new OrderObjectException('Plataforma não encontrada.'),
        };
    }

    public function addLog(
        string       $platform,
        string|array $payload,
        ?string      $event = null
    ): void
    {
        if (!$this->getOrder())
            return;

        OrderLog::create([
            'order_id' => $this->getOrder()->id,
            'platform' => $platform,
            'event' => $event,
            'payload' => is_array($payload) ? json_encode($payload) : $payload
        ]);
    }

    public function burn(): void
    {
        if (!$this->getOrder())
            return;

        $order = $this->getOrder();
        $order->burn = true;
        $order->save();
    }

    public function changeStatus(string $status): void
    {
        if (!$this->getOrder())
            return;

        $order = $this->getOrder();
        $order->status = $status;
        $order->save();
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function isGrid(): self
    {
        $this->isGrid = true;

        return $this;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        if ($product->parent_id)
            $this->isGrid();

        return $this;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getTotal(): float
    {
        $product = $this->getProduct();
        if (!$product)
            return $this->total;

        if ($product->price_sale > 0)
            return floatval($product->price_sale);

        return floatval($product->price);
    }


    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getUserPaymentIntegration(): UserPaymentIntegration
    {
        return $this->userPaymentIntegration;
    }

    public function getIsGrid(): bool
    {
        return $this->isGrid;
    }

    public function getParent(): Product
    {
        return Product::where('id', $this->getProduct()->parent_id)
            ->first();
    }

}
