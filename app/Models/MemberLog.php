<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLog extends Model
{
    protected $fillable = [
        'message',
        'direction',
        'bot_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'code');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
