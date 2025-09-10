<?php declare(strict_types=1);

namespace App\Enums;

enum PaymentStatusEnum: string
{

    const WAITING = 'WAITING';

    const SUCCESS = 'SUCCESS';

    const FAILURE = 'FAILURE';


    public static function legend(string $status): string
    {
        return match ($status) {
            self::SUCCESS => __('Aprovado'),
            self::FAILURE => __('Reprovado'),
            default => __('Aguardando')
        };
    }

    public static function color(string $status): string
    {
        return match ($status) {
            self::SUCCESS => 'success',
            self::FAILURE => 'danger',
            default => 'warning'
        };
    }

}
