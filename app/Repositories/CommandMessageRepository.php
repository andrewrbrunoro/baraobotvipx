<?php declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Cache\CommandMessageEnum;
use App\Models\CommandMessage;
use Illuminate\Support\Facades\Cache;

class CommandMessageRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(CommandMessage::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function create(int $user_id, string $key, string $command, string $text): ?CommandMessage
    {
        $cacheKey = CommandMessageEnum::message($user_id, $key, $command);

        return Cache::remember($cacheKey, 3600, function () use ($user_id, $key, $command, $text) {
            return $this->db
                ->updateOrCreate([
                    'user_id' => $user_id,
                    'key' => $key
                ], [
                    'command' => $command,
                    'text' => $text
                ]);
        });
    }

}
