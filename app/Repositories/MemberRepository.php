<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Member;

class MemberRepository extends Repository
{

    protected BotMemberRepository $botMemberRepository;

    public function __construct()
    {
        $this->botMemberRepository = BotMemberRepository::make();

        parent::__construct(Member::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function findByCode($code): ?Member
    {
        return $this->db
            ->where('code', $code)
            ->first();
    }

    public function find($member_id): ?Member
    {
        return $this->db
            ->find($member_id);
    }

    public function create(
        int $user_id,
        int $bot_id,
        $code,
        array $from = []
    ): ?Member
    {
        $member = $this->db
            ->firstOrCreate([
                'user_id' => $user_id,
                'code' => $code,
            ], [
                'name' => $from['firstname'] ?? sprintf('Usuário sem nome %s', $code),
                'lastname' => $from['lastname'] ?? '',
                'username' => $from['username'] ?? sprintf('Usuário sem username %s', $code),
                'language_code' => $from['language_code'] ?? 'en',
            ]);

        $this->botMemberRepository
            ->add(
                bot_id: $bot_id,
                member_id: $member->id,
            );

        return $member;
    }

}
