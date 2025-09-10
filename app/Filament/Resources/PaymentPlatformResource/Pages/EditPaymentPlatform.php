<?php

namespace App\Filament\Resources\PaymentPlatformResource\Pages;

use App\Filament\Resources\PaymentPlatformResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentPlatform extends EditRecord
{
    protected static string $resource = PaymentPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
