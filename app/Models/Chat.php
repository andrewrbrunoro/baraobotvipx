<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory;

    public function bots(): HasMany
    {
        return $this->hasMany(BotChat::class);
    }

    public function bot(): HasOne
    {
        return $this->hasOne(BotChat::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function permissions(): HasOne
    {
        return $this->hasOne(ChatPermission::class);
    }
}
