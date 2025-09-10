<?php

namespace App\Filament\Resources\ChatMemberResource\Pages;

use App\Filament\Resources\ChatMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChatMembers extends ListRecords
{
    protected static string $resource = ChatMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
