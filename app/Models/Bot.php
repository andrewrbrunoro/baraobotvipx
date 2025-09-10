<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'phone',
        'name',
        'status',
        'test',
        'host',
        'connection_key',
        'bearer_token',
        'tries',
        'last_try',
        'principal',
        'username',
        'token',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(BotChat::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(BotCommand::class);
    }

}
