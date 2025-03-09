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
        'location',
        'address',
        'price',
        'renting_duration',
        'features',
        'images',
        'description',
        'status',
        'average_reviews',
        'user_id',
        'category_id'
    ];

    protected $casts = [
        'images' => 'json',
        'location' => 'array',
        'features' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
