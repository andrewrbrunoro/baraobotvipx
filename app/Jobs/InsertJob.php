<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InsertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $data)
    {
        //
    }

    function dateValidate ($value) {
        try {
            $result = \Carbon\Carbon::parse($value);
            return $result->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->data as $datum) {
            $id = $datum['id'];
            $name = $datum['name'];
            $date = $datum['date'];
            $phone = $datum['phone'];

            $result = \App\Models\Member::firstOrCreate([
                'code' => $id,
            ], [
                'user_id' => 8,
                'name' => $name,
                'phone' => $phone ?? null,
            ]);
            if (!$result) {
                info(sprintf('erro ao inserir o %s', json_encode($datum)));
                continue;
            }

            if (!empty($date)) {
                $formatDate = $this->dateValidate($date);
                info($formatDate);
                if (!$formatDate) continue;

                \App\Models\ChatMember::firstOrCreate([
                    'chat_id' => 7,
                    'member_id' => $result->id,
                ], [
                    'expired_at' => $formatDate,
                    'already_kicked' => 0,
                    'from' => 'ASTRON'
                ]);
            }
        }
    }
}
