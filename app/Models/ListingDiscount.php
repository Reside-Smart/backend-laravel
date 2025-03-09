<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingDiscount extends Model
{
    protected $fillable = ['listing_id', 'percentage', 'start_date', 'end_date'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
