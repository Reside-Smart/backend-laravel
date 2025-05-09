<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RentalOption;
use App\Models\Listing;


class RentalOptionsController extends Controller
{
    public function cancelRentalOption(RentalOption $rentalOption)
    {
        $rentalOption->is_cancelled = 0;
        $rentalOption->save();

        return response()->json([
            'message' => 'Rental option cancelled successfully.',
            'data' => $rentalOption
        ]);
    }


    public function updateRentalOption(Request $request,  RentalOption $rentalOption)
    {
        $request->validate([
            'duration' => 'required|integer|min:1',
            'unit' => 'required|string|in:Day,Week,Month,Year',
            'price' => 'required|numeric|min:0',
        ]);

        $rentalOption->update([
            'duration' => $request->duration,
            'unit' => $request->unit,
            'price' => $request->price,
        ]);
        return response()->json(['message' => 'Rental option updated successfully']);
    }

    public function addRentalOption(Request $request, Listing $lis)
    {
        $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'duration' => 'required|integer|min:1',
            'unit' => 'required|string|in:Day,Week,Month,Year',
            'price' => 'required|numeric|min:0',
        ]);

        $rentalOption = RentalOption::create([
            'listing_id' => $request->listing_id,
            'duration' => $request->duration,
            'unit' => $request->unit,
            'price' => $request->price,
        ]);


        return response()->json([
            'message' => 'Rental option added successfully.',
            'data' => $rentalOption,
        ]);
    }
}
