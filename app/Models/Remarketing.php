<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remarketing extends Model
{
    protected $fillable = [
        'campaign',
        'member_id',
        'campaign_id',
        'bot_id',
        'status',
        'executed_at',
        'error_message'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }
}
