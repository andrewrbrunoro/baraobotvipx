<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\ChatMember;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChatMemberRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(ChatMember::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function isMember($chat_id, $member_id): bool
    {
        return ChatMember::where('chat_id', $chat_id)
            ->where('member_id', $member_id)
            ->where('already_kicked', 0)
            ->exists();
    }

    public function subscribes(int $member_id): Collection
    {

        return $this->db
            ->where('member_id', $member_id)
            ->orderBy('created_at', 'desc')
            ->limit(1)
            ->get();
    }

    public function newMember(
        int|string $chat_id,
        int|string $member_id,
        Carbon $expired_at
    ): ?ChatMember
    {
        return $this->db
            ->create([
                'chat_id' => $chat_id,
                'member_id' => $member_id,
                'expired_at' => $expired_at
            ]);
    }

    public function listenExpiredDate(int $chunk = 25): Collection
    {
        return $this
            ->db
            ->where('expired_at', '<=', now())
            ->where('already_kicked', false)
            ->orderBy('created_at', 'desc')
            ->groupBy('member_id')
            ->get()
            ->chunk($chunk);
    }

}
