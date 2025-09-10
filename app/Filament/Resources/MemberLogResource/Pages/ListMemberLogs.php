<?php

namespace App\Filament\Resources\MemberLogResource\Pages;

use App\Filament\Resources\MemberLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMemberLogs extends ListRecords
{
    protected static string $resource = MemberLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
