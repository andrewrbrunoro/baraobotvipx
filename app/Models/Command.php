<?php declare(strict_types=1);

namespace App\Models;

use App\Casts\SerializeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    use HasFactory;

    protected $casts = [
        'parameters' => SerializeCast::class,
        'aliases' => 'array',
    ];

    protected $appends = [
        'select_name'
    ];

    public function getSelectNameAttribute(): string
    {
        return sprintf('/%s - %s', $this->attributes['name'], $this->attributes['description']);
    }

}
