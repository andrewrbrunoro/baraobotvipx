<?php

namespace App\Filament\Resources\TelegramVideoResource\Pages;

use App\Filament\Resources\TelegramVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelegramVideos extends ListRecords
{
    protected static string $resource = TelegramVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
