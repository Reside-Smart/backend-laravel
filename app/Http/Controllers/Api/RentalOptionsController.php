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
        // Get the listing and current unit type of this option
        $listing = $rentalOption->listing;
        $currentUnit = $rentalOption->unit;

        // Check if this is the only duration=1 option for this unit
        if ($rentalOption->duration == 1) {
            $sameUnitOptions = RentalOption::where('listing_id', $listing->id)
                ->where('unit', $currentUnit)
                ->where('is_cancelled', 1)
                ->where('id', '!=', $rentalOption->id)
                ->where('duration', 1)
                ->count();

            if ($sameUnitOptions == 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot cancel this option. Each unit type must have at least one rental option with duration of 1. This is the only one for {$currentUnit}.",
                ], 400);
            }
        }

        $rentalOption->is_cancelled = 0;
        $rentalOption->save();

        return response()->json([
            'message' => 'Rental option cancelled successfully.',
            'data' => $rentalOption
        ]);
    }


    public function updateRentalOption(Request $request, RentalOption $rentalOption)
    {
        $request->validate([
            'duration' => 'required|integer|min:1',
            'unit' => 'required|string|in:Day,Week,Month,Year',
            'price' => 'required|numeric|min:0',
            'is_weekend' => 'boolean',
        ]);

        $listing = $rentalOption->listing;
        $currentUnit = $rentalOption->unit;
        $newUnit = $request->unit;
        $isDurationChanging = $rentalOption->duration != $request->duration;
        $isUnitChanging = $currentUnit != $newUnit;

        // If we're changing this from duration=1 to something else, make sure it's not the only duration=1 for this unit
        if ($rentalOption->duration == 1 && $isDurationChanging) {
            $sameUnitOptions = RentalOption::where('listing_id', $listing->id)
                ->where('unit', $currentUnit)
                ->where('is_cancelled', 1)
                ->where('id', '!=', $rentalOption->id)
                ->where('duration', 1)
                ->count();

            if ($sameUnitOptions == 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot update this option. Each unit type must have at least one rental option with duration of 1. This is the only one for {$currentUnit}.",
                ], 400);
            }
        }

        // If we're changing the unit, check that the new unit would have at least one duration=1 option
        if ($isUnitChanging && $request->duration != 1) {
            $newUnitHasDurationOne = RentalOption::where('listing_id', $listing->id)
                ->where('unit', $newUnit)
                ->where('is_cancelled', 1)
                ->where('duration', 1)
                ->exists();

            if (!$newUnitHasDurationOne) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot change to unit {$newUnit} with duration other than 1. Each unit type must have at least one rental option with duration of 1.",
                ], 400);
            }
        }

        $rentalOption->update([
            'duration' => $request->duration,
            'unit' => $request->unit,
            'price' => $request->price,
            'is_weekend' => $request->is_weekend ?? false,
        ]);

        return response()->json(['message' => 'Rental option updated successfully']);
    }

    public function addRentalOption(Request $request)
    {
        $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'duration' => 'required|integer|min:1',
            'unit' => 'required|string|in:Day,Week,Month,Year',
            'price' => 'required|numeric|min:0',
            'is_weekend' => 'boolean',
        ]);

        // If adding an option with a unit that already exists and duration is not 1,
        // check if that unit already has a duration=1 option
        if ($request->duration != 1) {
            $unitHasDurationOne = RentalOption::where('listing_id', $request->listing_id)
                ->where('unit', $request->unit)
                ->where('is_cancelled', 1)
                ->where('duration', 1)
                ->exists();

            if (!$unitHasDurationOne) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot add option with duration {$request->duration} to unit {$request->unit}. Please add an option with duration 1 for this unit first.",
                ], 400);
            }
        }

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

    // Add a method for deleting rental options
    public function deleteRentalOption(RentalOption $rentalOption)
    {
        $listing = $rentalOption->listing;
        $currentUnit = $rentalOption->unit;

        // Check if this is the only duration=1 option for this unit
        if ($rentalOption->duration == 1) {
            $sameUnitOptions = RentalOption::where('listing_id', $listing->id)
                ->where('unit', $currentUnit)
                ->where('is_cancelled', 1)
                ->where('id', '!=', $rentalOption->id)
                ->where('duration', 1)
                ->count();

            if ($sameUnitOptions == 0) {
                // Check if this is the last option for this unit
                $anyUnitOptions = RentalOption::where('listing_id', $listing->id)
                    ->where('unit', $currentUnit)
                    ->where('is_cancelled',)
                    ->where('id', '!=', $rentalOption->id)
                    ->count();

                if ($anyUnitOptions > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete this option. Each unit type must have at least one rental option with duration of 1. This is the only one for {$currentUnit}.",
                    ], 400);
                }
                // If it's the last option for this unit, we allow deletion
            }
        }

        $rentalOption->delete();

        return response()->json([
            'message' => 'Rental option deleted successfully.',
        ]);
    }

    public function getByListing($listingId)
    {
        $listing = Listing::findOrFail($listingId);

        if ($listing->type != 'rent') {
            return response()->json([
                'data' => []
            ]);
        }

        $options = RentalOption::where('listing_id', $listingId)
            ->where('is_cancelled', 0)
            ->select('id', 'duration', 'unit', 'price')
            ->get();

        return response()->json([
            'data' => $options
        ]);
    }
}
