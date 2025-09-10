<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    public function chatMembers(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function memberLogs(): HasMany
    {
        return $this->hasMany(MemberLog::class, 'member_id', 'code');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'member_id', 'id');
    }

    public function chatMember(): BelongsTo
    {
        return $this->belongsTo(ChatMember::class, 'id', 'member_id');
    }

    public function botMembers()
    {
        return $this->hasMany(BotMember::class);
    }

    public function lastBotMember()
    {
        return $this->hasOne(BotMember::class)
            ->select('bot_id')
            ->latest()
            ->withDefault();
    }
}
