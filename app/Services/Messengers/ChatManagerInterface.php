<?php declare(strict_types=1);

namespace App\Services\Messengers;

use App\Models\Bot;
use App\Models\Chat;
use Telegram\Bot\Objects\ChatMember;

interface ChatManagerInterface
{
    public function __construct(...$arguments);

    public static function make(...$arguments): self;

    public function getChatMember(int|string $user_id): bool|ChatMember;

    public function sendMessage(int|string $user_id, string $message): self;

    public function asyncAddMember(int|string $user_id, string $message = ''): void;

    public function addMember(mixed $params): bool;

    public function asyncRemoveMember(int|string $user_id): void;

    public function removeMember(mixed $params): bool;

    public function inviteLink(mixed $params): ?string;

    public function changeChatPermissions(array $params = []): self;

    public function setChat(Chat $chat): self;

    public function setBot(Bot $bot): self;

    public function getBot(): Bot;

    public function getChat(): ?Chat;
}
