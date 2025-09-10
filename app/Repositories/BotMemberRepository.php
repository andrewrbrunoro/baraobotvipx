<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\BotMember;

class BotMemberRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(BotMember::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function add(int $bot_id, int $member_id): BotMember
    {
        return $this->db
            ->firstOrCreate([
                'bot_id' => $bot_id,
                'member_id' => $member_id,
            ]);
    }

}
