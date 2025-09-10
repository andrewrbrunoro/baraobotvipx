<?php declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignEventEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'event',
        'timer_seconds',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'timer_seconds' => 'integer',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'event' => CampaignEventEnum::class,
    ];

    public function remarketings(): HasMany
    {
        return $this->hasMany(Remarketing::class, 'campaign', 'code');
    }

    public function canExecute(): bool
    {
        return $this->is_active;
    }

    public function shouldExecute(array $data): bool
    {
        if (!$this->canExecute()) {
            return false;
        }

        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $key => $value) {
            if (!isset($data[$key]) || $data[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
