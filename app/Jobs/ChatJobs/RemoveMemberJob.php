<?php

namespace App\Jobs\ChatJobs;

use App\Services\Messengers\ChatManagerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveMemberJob implements ShouldQueue
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
        $chatManager = $this->chatManager;

        $chatManager
            ->removeMember([
                'user_id' => $this->userId,
                'message' => $this->message,
            ]);
    }
}
