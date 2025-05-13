<?php

namespace App\Http\Controllers;

use App\Models\ListingDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ListingDiscountController extends Controller
{

    public function getAllListingDiscounts(Request $request)
    {
        return \response()->json([
            'message' => "Discounts Fetched Successfully!",
            'data' => ListingDiscount::with(['listing.rentalOptions'])
                ->where('status', 'active')
                ->limit(5)
                ->get()
        ]);
    }

    public function userListingDiscounts(Request $request)
    {
        $discounts = ListingDiscount::with(['listing.rentalOptions'])
            ->whereHas('listing', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->get();

        if ($request->has('status') && in_array($request->status, ['active', 'inactive', 'expired', 'deactivated'])) {
            $discounts = $discounts->where('status', $request->status)->values();
        }

        return response()->json([
            'message' => 'Discounts retrieved successfully',
            'discounts' => $discounts
        ], 200);
    }

    public function addDiscount(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'rental_option_id' => 'nullable|exists:rental_options,id',
        ]);


        $discount = ListingDiscount::create($validated)->fresh();

        return response()->json([
            'message' => 'Discount added successfully!',
            'discount' => $discount
        ], 201);
    }

    public function deleteDiscount(ListingDiscount $discount)
    {
        // Check if there are any transactions associated with this discount
        $hasTransactions = $discount->transactions()->exists();

        if ($hasTransactions) {
            // Deactivate the discount
            $discount->status = 'deactivated';
            $discount->save();

            return response()->json([
                'message' => 'Discount has related transactions and has been deactivated.',
            ], 200);
        } else {
            // Delete the discount
            $discount->delete();

            return response()->json([
                'message' => 'Discount deleted successfully.',
            ], 200);
        }
    }
}
