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
        'features',
        'images',
        'description',
        'status',
        'is_available',
        'average_reviews',
        'user_id',
        'category_id'
    ];

    protected $casts = [
        'images' => 'json',
        'location' => 'array',
        'features' => 'array',
        'is_favorite' => 'boolean'
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
    public function rentalOptions(): HasMany
    {
        return $this->hasMany(RentalOption::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(
            User::class,
            'favorites',
            'listing_id',
            'user_id'
        )->withTimestamps();
    }
}
