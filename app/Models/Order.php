<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'member_id',
        'bot_id',
        'product_id',
        'item_type',
        'item_id',
        'price',
        'price_sale',
        'total',
        'pix_code',
        'payment_link',
        'status',
        'platform',
        'platform_id',
        'qrcode',
        'qrcode_path'
    ];

    protected $appends = [
        'real_total'
    ];

    public function getRealTotalAttribute(): float
    {
        $price = $this->attributes['price'] ?? 0;
        $priceSale = $this->attributes['price_sale'] ?? 0;

        if ($priceSale > 0)
            return (float)$priceSale;

        return (float)$price;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
