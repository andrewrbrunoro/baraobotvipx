<?php

namespace App\Filament\Resources\ChatMemberResource\Pages;

use App\Filament\Resources\ChatMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatMember extends EditRecord
{
    protected static string $resource = ChatMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
