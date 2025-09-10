<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMember extends Model
{
    use HasFactory;

    protected $casts = [
        'expired_at' => 'datetime'
    ];

    protected $appends = [
        'is_expired'
    ];

    public function getIsExpiredAttribute(): bool
    {
        $expiredAt = $this->attributes['expired_at'];

        return now()->diffInSeconds($expiredAt) < 0;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)
            ->orderByDesc('created_at');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
