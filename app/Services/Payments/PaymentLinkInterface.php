<?php declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use MercadoPago\Resources\Payment\Payer;

interface PaymentLinkInterface
{

    public function __construct(PaymentService $paymentService);

    public static function make(PaymentService $paymentService): self;

    public function run(Order $order, ?Payer $payer = null): string;

}
