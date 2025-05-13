<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingDiscount extends Model
{
    protected $fillable = ['name', 'listing_id', 'percentage', 'start_date', 'end_date', 'status', 'rental_option_id'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'discount_id');
    }
}
