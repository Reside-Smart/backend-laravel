<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalOption extends Model
{
    protected $fillable = [
        'listing_id',
        'duration',
        'unit',
        'price',
        'is_cancelled',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
