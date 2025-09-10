<?php

namespace App\Filament\Resources\ChatMemberResource\Pages;

use App\Filament\Resources\ChatMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChatMember extends CreateRecord
{
    protected static string $resource = ChatMemberResource::class;
}
