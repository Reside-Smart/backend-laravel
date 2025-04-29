<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    function getUserFavorites(Request $request)
    {
        $user = Auth::user();

        // Eager-load any relationships you need (e.g. images, category, rentalOptions)
        $favorites = $user->favorites()
            ->get()
            ->map(function (Listing $listing) {
                // Optionally add an is_favorite flag (will always be true here)
                $listing->is_favorite = true;
                return $listing;
            });

        return response()->json([
            'listings' => $favorites,
        ], 200);
    }

    /**
     * Add the listing to the authenticated user’s favorites.
     */
    public function store(Request $request)
    {
        $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
        ]);

        $user = Auth::user();

        // attach() will ignore duplicates if already favorited
        $user->favorites()->syncWithoutDetaching($request->listing_id);

        return response()->json(['success' => true], 200);
    }

    /**
     * Remove the listing from the authenticated user’s favorites.
     */
    public function destroy($listingId)
    {
        $user = Auth::user();

        // detach() removes the pivot record
        $user->favorites()->detach($listingId);

        return response()->json(['success' => true], 200);
    }
}
