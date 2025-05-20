<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ReviewsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'listing_id' => 'required|exists:listings,id',
        ]);

        $userId = Auth::id();

        $review = Review::create([
            'text' => $request->text,
            'user_id' => $userId,
            'listing_id' => $request->listing_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review saved successfully',
            'data' => $review,
        ], 200);
    }

    public function userReviews($listingId)
    {
        $userId = Auth::id();

        $reviews = Review::where('user_id', $userId)
            ->where('listing_id', $listingId)
            ->get(['text']);

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }
    public function getReviews($listingId)
    {
        $reviews = Review::with('user')
            ->where('listing_id', $listingId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'reviews' => $reviews,
        ]);
    }
}
