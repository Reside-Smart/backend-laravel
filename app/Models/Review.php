<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'rating',
        'text',
        'listing_id',
        'user_id'
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listings::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
