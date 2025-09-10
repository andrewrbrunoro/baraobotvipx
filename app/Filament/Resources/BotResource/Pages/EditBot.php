<?php declare(strict_types=1);

namespace App\Filament\Resources\BotResource\Pages;

use App\Filament\Resources\BotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBot extends EditRecord
{
    protected static string $resource = BotResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
