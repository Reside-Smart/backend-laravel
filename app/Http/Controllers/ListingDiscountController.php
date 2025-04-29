<?php

namespace App\Http\Controllers;

use App\Models\ListingDiscount;
use Illuminate\Http\Request;

class ListingDiscountController extends Controller
{
    public function getAllListingDiscounts(Request $request)
    {
        return \response()->json([
            'message' => "Discounts Fetched Successfully!",
            'data' => ListingDiscount::with('listing')
                ->where('status', 'active')
                ->limit(5)
                ->get()
        ]);
    }
}
