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

        $review->load(['user', 'listing']);
        // dispatch event to send notification
        event(new \App\Events\ReviewCreated($review));

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
            ->orderBy('created_at', 'desc')
            ->get(['id', 'text', 'created_at']);

        return response()->json([
            'status' => true,
            'data' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'text' => $review->text,
                    'created_at' => $review->created_at->format('Y-m-d'),
                ];
            }),
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
    public function update(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $userId = Auth::id();

        $review = Review::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found or unauthorized',
            ], 404);
        }

        $review->update([
            'text' => $request->text,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review updated successfully',
            'data' => $review,
        ]);
    }

    public function destroy($id)
    {
        $userId = Auth::id();

        $review = Review::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found or unauthorized',
            ], 404);
        }

        $review->delete();

        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}
