<?php declare(strict_types=1);

namespace App;

class MarketplaceManager
{
    public static function make(): self
    {
        return new self();
    }

    public function marketplaceFee(float $price): float
    {
        return 0;
        return $price * ($this->getFixedFee() / 100);
    }

    public function getFixedFee(): float
    {
        return 0;
        return 5;
    }

}
