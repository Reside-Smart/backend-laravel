<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'name',
        'type',
        'longitude',
        'latitude',
        'address',
        'price',
        'price_cycle',
        'features',
        'description',
        'status',
        'average_reviews',
        'user_id',
        'category_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class);
    }
    public function discounts(): HasMany
    {
        return $this->hasMany(ListingDiscount::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
