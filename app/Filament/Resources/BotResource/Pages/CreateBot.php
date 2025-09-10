<?php declare(strict_types=1);

namespace App\Filament\Resources\BotResource\Pages;

use App\Filament\Resources\BotResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBot extends CreateRecord
{
    protected static string $resource = BotResource::class;

    protected function getFormActions(): array
    {
        return [];
    }
}
