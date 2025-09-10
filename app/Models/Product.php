<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,
        SoftDeletes;

    public function grids(): HasMany
    {
        return $this->hasMany(Product::class,'parent_id');
    }

    public function setDescriptionAttribute(?string $description = ''): void
    {
        if (!empty($description)) {
            $allowTags = '<b><strong><i><em><code><s><strike><del><u><pre><br>';
            $description = strip_tags($description, $allowTags);
            $description = str_replace('<br>', PHP_EOL, $description);
            $description = rtrim($description);
        }

        $this->attributes['description'] = $description;
    }
}
