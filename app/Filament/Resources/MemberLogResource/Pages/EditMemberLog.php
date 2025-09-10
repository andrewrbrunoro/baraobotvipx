<?php

namespace App\Filament\Resources\MemberLogResource\Pages;

use App\Filament\Resources\MemberLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemberLog extends EditRecord
{
    protected static string $resource = MemberLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
