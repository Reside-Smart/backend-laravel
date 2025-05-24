<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_type',
        'total_price',
        'amount_paid',
        'payment_status',
        'payment_method',
        'check_in_date',
        'check_out_date',
        'listing_id',
        'buyer_id',
        'seller_id',
        'discount_id',
        'rental_option_id',
        'quantity',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    public function listingDiscount(): BelongsTo
    {
        return $this->belongsTo(ListingDiscount::class, 'discount_id');
    }
    public function rentalOption(): BelongsTo
    {
        return $this->belongsTo(RentalOption::class);
    }
}
