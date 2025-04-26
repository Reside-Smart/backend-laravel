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
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
