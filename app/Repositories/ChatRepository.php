<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Bot;
use App\Models\Chat;
use Illuminate\Support\Collection;

class ChatRepository extends Repository
{

    protected BotChatRepository $botChatRepository;

    public function __construct()
    {
        $this->botChatRepository = BotChatRepository::make();

        parent::__construct(Chat::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function findByCode($code): ?Chat
    {
        return $this->db
            ->where('code', $code)
            ->first();
    }

    public function userChats(int $user_id): Collection
    {
        return $this
            ->db
            ->where('user_id', $user_id)
            ->pluck('id', 'id');
    }

    public function find(int $user_id, int $chat_id): ?Chat
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('id', $chat_id)
            ->first();
    }

    public function setup(array $chat_params, Bot $bot, int|string $verified_by): ?Chat
    {
        $chatCode = $chat_params['chat_code'];

        if (!$this->canSetup($chatCode)) return null;

        $chatName = $chat_params['chat_name'] ?? null;

        $chat = $this->db
            ->firstOrCreate([
                'user_id' => $bot->user_id,
                'code' => $chatCode,
            ], [
                'name' => $chatName ?? sprintf('Grupo Sem Nome - %s', $chatCode),
                'is_group' => true,
            ]);

        $this->botChatRepository
            ->addChat(
                $chat->id,
                $bot->id,
                $verified_by,
                now(),
            );

        return $chat;
    }

    public function canSetup(int|string $chat_code): bool
    {
        $exists = $this->alreadyExists($chat_code);
        if (!$exists) return true;

        $hasBot = $this->botChatRepository->chatHasBot($exists->id);
        if (!$hasBot) return true;

        return false;
    }

    public function alreadyExists(int|string $chat_code): ?Chat
    {
        return $this->db
            ->where('code', $chat_code)
            ->first();
    }

}
