<?php declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Cache\BotCacheEnum;
use App\Models\Bot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class BotRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(Bot::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function myBots(): Collection
    {
        $user = Auth::user();

        return $this->db
            ->where('user_id', $user->id)
            ->get();
    }

    public function create(
        int|string $code,
        array      $bot_data,
        ?int       $user_id = null
    ): ?Bot
    {
        $data = collect($bot_data);

        $result = Bot::updateOrCreate([
            'user_id' => $user_id ?? Auth::user()->id,
            'code' => $code,
        ], [
            ...$data->only([
                'token',
                'first_name',
                'username',
                'can_join_groups',
                'can_read_all_group_messages',
                'supports_inline_queries',
                'can_connect_to_business',
                'has_main_web_app',
                'owner_code',
                'is_verified',
            ])->toArray(),
        ]);

        if (!$result) return null;

        $this->generateCache($result);

        return $result;
    }

    public function updatePinVerified(
        int|Bot    $bot,
        int|string $owner_code
    ): bool
    {
        $bot = is_int($bot) ? $this->findById($bot) : $bot;
        if (!$bot)
            return false;

        $bot->is_verified = true;

        $result = $bot->save();
        if (!$result)
            return false;

        $this->generateCache($bot->refresh());

        return true;
    }

    public function findById(int $id): ?Bot
    {
        return $this->db->find($id);
    }

    public function findByToken(string $token): ?Bot
    {
        return Cache::remember(BotCacheEnum::bot($token), 60 * 24 * 30, function () use ($token) {
            return $this->db
                ->where('token', $token)
                ->first();
        });
    }

    public function generateCache(Bot $bot): void
    {
        $cacheKey = BotCacheEnum::bot($bot->token);

        Cache::forget($cacheKey);
        Cache::add($cacheKey, $bot);
    }

    public function isOwner(int $bot_id): bool
    {
        return $this->db
            ->where('id', $bot_id)
            ->where('user_id', Auth::user()->id)
            ->exists();
    }
}
