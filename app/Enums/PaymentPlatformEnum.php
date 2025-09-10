<?php declare(strict_types=1);

namespace App\Enums;

enum PaymentPlatformEnum: string
{

    const MERCADO_PAGO = 'MERCADO_PAGO';

    const PIX_MERCADO_PAGO = 'PIX_MERCADO_PAGO';

    const PUSHINPAY = 'pushinpay';

}
