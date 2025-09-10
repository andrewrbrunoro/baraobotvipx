<?php

namespace App\Filament\Resources\TelegramVideoResource\Pages;

use App\Filament\Resources\TelegramVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelegramVideo extends EditRecord
{
    protected static string $resource = TelegramVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
