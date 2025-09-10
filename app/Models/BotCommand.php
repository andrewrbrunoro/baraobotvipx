<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotCommand extends Model
{
    use HasFactory;

    public function getDescriptionAttribute(?string $description): ?string
    {
        if (!empty($description))
            return $description;

        return $this->command?->description;
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }
}
