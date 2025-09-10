<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatPermission extends Model
{
    use HasFactory;

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
