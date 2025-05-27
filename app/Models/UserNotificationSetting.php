<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transactions',
        'new_listings',
        'messages',
        'discounts',
        'reviews',
    ];

    protected $casts = [
        'transactions' => 'boolean',
        'new_listings' => 'boolean',
        'messages' => 'boolean',
        'discounts' => 'boolean',
        'reviews' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
