<?php declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Models\ChatMember;
use App\Repositories\BotChatRepository;
use App\Repositories\MemberRepository;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveMemberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatMember $chatMember,
        public ?string    $text = null,
        public ?array     $reply = []
    )
    {
    }

    public function handle(): void
    {
        \Log::info('RemoveMemberJob: Starting processing', [
            'chat_member_id' => $this->chatMember->id ?? 'unknown',
            'chat_id' => $this->chatMember->chat_id ?? 'unknown',
            'member_id' => $this->chatMember->member_id ?? 'unknown'
        ]);

        $result = BotChatRepository::make()
            ->getChatAndBot($this->chatMember->chat_id);

        \Log::info('RemoveMemberJob: BotChatRepository result', [
            'result' => $result,
            'result_type' => gettype($result),
            'is_null' => is_null($result)
        ]);

        if (!$result || !isset($result['chat']) || !$result['chat']) {
            \Log::warning('RemoveMemberJob: No chat found or result is null', [
                'chat_id' => $this->chatMember->chat_id ?? 'unknown',
                'result' => $result
            ]);
            return;
        }

        $member = MemberRepository::make()
            ->find($this->chatMember->member_id);

        \Log::info('RemoveMemberJob: Member lookup result', [
            'member_id' => $this->chatMember->member_id ?? 'unknown',
            'member_found' => !is_null($member),
            'member_data' => $member ? $member->toArray() : null
        ]);

        if (!$member) {
            \Log::warning('RemoveMemberJob: Member not found', [
                'member_id' => $this->chatMember->member_id ?? 'unknown'
            ]);
            return;
        }

        // Primeira tentativa com o bot vinculado
        $defaultBot = BotChatRepository::make()->getDefaultBot();
        \Log::info('RemoveMemberJob: Default bot lookup', [
            'default_bot_found' => !is_null($defaultBot),
            'default_bot_data' => $defaultBot ? $defaultBot->toArray() : null
        ]);

        $chatTelegramManager = ChatTelegramManager::make()
            ->setBot($defaultBot)
            ->setChat($result['chat']);

        $chatMember = $chatTelegramManager->getChatMember(
            $member->code,
            false
        );

        \Log::info('RemoveMemberJob: Chat member lookup', [
            'member_code' => $member->code ?? 'unknown',
            'chat_member_found' => !is_null($chatMember),
            'chat_member_status' => $chatMember ? $chatMember->status : 'unknown'
        ]);

        if ($chatMember && $chatMember->status === 'creator') {
            \Log::info('RemoveMemberJob: Member is creator, skipping removal');
            return;
        }

        $removeResult = $chatTelegramManager->removeMember([
            'user_id' => $member->code,
            'text' => $this->text
        ], empty($this->chatMember->from));

        \Log::info('RemoveMemberJob: Remove member result', [
            'remove_result' => $removeResult,
            'from_empty' => empty($this->chatMember->from)
        ]);

        if ($removeResult) {
            $this->chatMember->already_kicked = true;
            $this->chatMember->save();
            \Log::info('RemoveMemberJob: Member successfully removed and marked as kicked');
            return;
        }

        // Se falhou ou não tem bot vinculado, tenta com o bot padrão
        // $defaultBot = BotChatRepository::make()->getDefaultBot();
        // if ($defaultBot) {
        //     $chatTelegramManager = ChatTelegramManager::make()
        //         ->setBot($defaultBot)
        //         ->setChat($result['chat']);

        //     $chatMember = $chatTelegramManager->getChatMember(
        //         $member->code,
        //         false
        //     );

        //     if ($chatMember && $chatMember->status === 'creator')
        //         return;

        //     $result = $chatTelegramManager->removeMember([
        //         'user_id' => $member->code,
        //         'text' => $this->text
        //     ], empty($this->chatMember->from));

        //     if ($result) {
        //         $this->chatMember->already_kicked = true;
        //         $this->chatMember->save();
        //     }
        // }
    }
}
