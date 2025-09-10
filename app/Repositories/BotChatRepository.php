<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\BotChat;
use App\Models\Bot;
use Carbon\Carbon;

class BotChatRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(BotChat::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function getDefaultBot(): ?Bot
    {
        return Bot::where('is_admin', true)
            ->first();
    }

    public function getChatAndBot(int $chat_id): ?array
    {
        $result = $this->db
            ->where('chat_id', $chat_id)
            ->first();

        if (!$result) {
            return null;
        }

        $bot = $result->bot;
        $chat = $result->chat;

        // Se o bot não estiver disponível, tenta usar o bot padrão
        if (!$bot || !$bot->is_verified) {
            $defaultBot = $this->getDefaultBot();
            if ($defaultBot) {
                $bot = $defaultBot;
            }
        }

        return [
            'chat' => $chat,
            'bot' => $bot,
        ];
    }

    public function addChat(int $chat_id, int $bot_id, int|string $verified_by = null, ?Carbon $verified_at = null)
    {
        return $this->db
            ->firstOrCreate([
                'chat_id' => $chat_id,
                'bot_id' => $bot_id,
            ], [
                'verified_by' => $verified_by,
                'verified_at' => $verified_at,
            ]);
    }

    public function chatHasBot(int|string $chat_id): bool
    {
        return $this->db
            ->where('chat_id', $chat_id)
            ->count() > 0;
    }

}
