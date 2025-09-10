<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuthPin;
use Illuminate\Support\Str;

class AuthPinRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(AuthPin::class);
    }

    public function generatePin(int|string $bot_id, int|string $chat_id): AuthPin
    {
        $already = $this->alreadyGenerate($bot_id, $chat_id);
        if ($already)
            return $already;

        $today = now();

        return AuthPin::create([
            'bot_id' => $bot_id,
            'chat_id' => $chat_id,
            'pin' => Str::uuid()->toString(),
            'expire_at' => $today->addSeconds(120),
        ]);
    }

    public function alreadyGenerate(int|string $bot_id, int|string $chat_id): ?AuthPin
    {
        $today = now();

        return $this->db
            ->where('bot_id', $bot_id)
            ->where('chat_id', $chat_id)
            ->whereNull('verified_at')
            ->where('expire_at', '>', $today)
            ->first();
    }

}
