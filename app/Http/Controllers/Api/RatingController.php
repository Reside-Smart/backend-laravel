<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'listing_id' => 'required|exists:listings,id',
        ]);

        $userId = Auth::id();

        $rating = Rating::create(
            [
                'rating' => $request->rating,
                'user_id' => $userId,
                'listing_id' => $request->listing_id,
            ]
        );
        $rating->listing->updateAverageRating();

        return response()->json([
            'status' => true,
            'message' => 'Rating saved successfully',
            'data' => $rating,
        ], 200);
    }

    public function showRatedBefore($listingId)
    {
        $userId = Auth::id();
        $rating = Rating::where('user_id', $userId)
            ->where('listing_id', $listingId)
            ->first();

        if ($rating) {
            return response()->json(['rating' => $rating->rating], 200);
        }

        return response()->json(['rating' => null], 200);
    }

    public function getRatingsByListing($listingId)
    {
        $ratings = Rating::where('listing_id', $listingId)
            ->select('id', 'user_id', 'listing_id', 'rating', 'created_at')
            ->get();


        return response()->json([
            'ratings' => $ratings
        ]);
    }
}
