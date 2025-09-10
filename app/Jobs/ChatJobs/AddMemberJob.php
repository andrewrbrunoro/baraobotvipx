<?php

namespace App\Jobs\ChatJobs;

use App\Repositories\ChatMemberRepository;
use App\Services\Messengers\ChatManagerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class AddMemberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatManagerInterface $chatManager,
        public int|string           $userId,
        public string               $message = '',
    )
    {
    }

    public function handle(): void
    {
        $already = ChatMemberRepository::make()
            ->isMember(
                $this->chatManager->getChat()->id,
                $this->userId,
            );

        if ($already) {
            return;
        }

        $chatManager = $this->chatManager;

        $chatManager
            ->addMember([
                'user_id' => $this->userId,
                'message' => $this->message,
            ]);
    }
}
