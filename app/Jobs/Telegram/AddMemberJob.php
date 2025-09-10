<?php declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Models\ChatMember;
use App\Repositories\BotChatRepository;
use App\Repositories\ChatMemberRepository;
use App\Repositories\MemberRepository;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AddMemberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatMember|array $chatMember
    )
    {
    }

    public function handle(): void
    {
        $chatId = $this->chatMember instanceof ChatMember
            ? $this->chatMember->chat_id
            : $this->chatMember['chat_id'];

        $memberId = $this->chatMember instanceof ChatMember
            ? $this->chatMember->member_id
            : $this->chatMember['member_id'];

        $result = BotChatRepository::make()
            ->getChatAndBot((int)$chatId);

        $chat = $result['chat'];

        $chatTelegramManager = ChatTelegramManager::make()
            ->setBot($result['bot'])
            ->setChat($chat);

        $member = MemberRepository::make()
            ->find($memberId);

        if (!$member)
            return;

        $chatMember = $chatTelegramManager->getChatMember(
            $member->code,
            false
        );

        if ($chatMember->status === 'creator')
            return;

        $chatTelegramManager->addMember([
            'user_id' => $member->code,
            'text' => $data['text'] ?? null
        ]);

        if ($this->chatMember instanceof ChatMember) {
            $this->chatMember->save();
        } else {
            ChatMemberRepository::make()
                ->newMember(
                    $this->chatMember['chat_id'],
                    $this->chatMember['member_id'],
                    $this->chatMember['expired_at']
                );
        }
    }
}
