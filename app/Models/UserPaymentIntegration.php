<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'integration_code',
        'token_type',
        'scope',
        'access_token',
        'refresh_token',
        'public_key',
        'expire_in',
        'expire_at',
        'live_mode'
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'live_mode' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
