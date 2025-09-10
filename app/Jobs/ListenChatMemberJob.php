<?php declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Telegram\RemoveMemberJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class ListenChatMemberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Collection $chat_members)
    {
    }

    public function handle(): void
    {
        \Log::info('ListenChatMemberJob: Starting processing', [
            'chat_members_count' => $this->chat_members->count(),
            'chat_members_data' => $this->chat_members->toArray()
        ]);

        foreach ($this->chat_members as $index => $chat_member) {
            try {
                \Log::info('ListenChatMemberJob: Processing member', [
                    'index' => $index,
                    'chat_member_id' => $chat_member->id ?? 'unknown',
                    'chat_id' => $chat_member->chat_id ?? 'unknown',
                    'member_id' => $chat_member->member_id ?? 'unknown',
                    'expired_at' => $chat_member->expired_at ?? 'unknown'
                ]);

                dispatch(
                    new RemoveMemberJob(
                        $chat_member,
                        <<<HTML
                        ✨ Sua assinatura ao nosso grupo vip acabou!

                        🚨  Renove agora mesmo e acesse o melhor grupo porno do telegram!
                        ❣️Clique em /ofertas para renovar seu plano!
                        HTML
                    )
                );

                \Log::info('ListenChatMemberJob: Member dispatched successfully', [
                    'index' => $index,
                    'chat_member_id' => $chat_member->id ?? 'unknown'
                ]);

            } catch (\Exception $e) {
                \Log::error('ListenChatMemberJob: Failed to process member', [
                    'index' => $index,
                    'chat_member_id' => $chat_member->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }

        \Log::info('ListenChatMemberJob: Completed processing', [
            'processed_count' => $this->chat_members->count()
        ]);
    }
}
