<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\MemberLog;

class MemberLogRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(MemberLog::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function save(
        $member_id,
        $message_id,
        $name = null,
        $action = null,
        $options = null,
        $feedback = null
    ): void
    {
        try {
            $this->db
                ->firstOrCreate([
                    'message_id' => $message_id,
                    'member_id' => $member_id,
                    'name' => $name,
                    'action' => $action,
                    'options' => $options,
                    'feedback' => $feedback,
                ]);
        } catch (\Exception $e) {
        }
    }

}
