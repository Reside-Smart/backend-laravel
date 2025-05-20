<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|string|in:sell,rent',
            'total_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_status' => 'required|string|in:paid,unpaid',
            'payment_method' => 'required|string|in:cash,stripe',
            'payment_date' => 'nullable|date',
            'check_in_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'listing_id' => 'required|exists:listings,id',
            'buyer_id' => 'required|exists:users,id',
            'seller_id' => 'required|exists:users,id',
            'discount_id' => 'nullable|exists:listing_discounts,id',
            'rental_option_id' => 'nullable|exists:rental_options,id',
        ]);


        if ($validated['transaction_type'] === 'rent') {
            $checkInDate = Carbon::parse($validated['check_in_date']);
            $checkOutDate = Carbon::parse($validated['check_out_date']);

            $conflictingTransaction = Transaction::where('listing_id', $validated['listing_id'])
                ->where('transaction_type', 'rent')
                ->where(function ($query) use ($checkInDate, $checkOutDate) {
                    $query->where('check_in_date', '<', $checkOutDate)
                        ->where('check_out_date', '>', $checkInDate);
                })
                ->first();

            if ($conflictingTransaction) {
                return response()->json([
                    'message' => 'The listing is not available for the selected date.',
                ], 409);
            }
        }

        $transaction = Transaction::create($validated);

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => $transaction
        ], 201);
    }

    public function getBookedDates($listingId)
    {
        $bookings = Transaction::where('listing_id', $listingId)
            ->get(['check_in_date', 'check_out_date']);

        $bookedDates = collect();

        foreach ($bookings as $booking) {
            $start = Carbon::parse($booking->check_in_date);
            $end = Carbon::parse($booking->check_out_date);

            for ($date = $start; $date->lte($end); $date->addDay()) {
                $bookedDates->push($date->toDateString());
            }
        }

        $uniqueBookedDates = $bookedDates->unique()->values();

        return response()->json([
            'bookedDates' => $uniqueBookedDates,
        ]);
    }

    public function getTransactions()
    {
        $userId = Auth::id();

        $transactions = Transaction::with(['listing', 'rentalOption'])
            ->where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->get();

        return response()->json([

            'message' => 'User transactions retrieved.',
            'data' => $transactions
        ]);
    }

    public function getSingleTransaction(Transaction $transaction)
    {
        $transaction->load(['listing', 'rentalOption', 'listingDiscount', 'listing.user', 'listing.rentalOptions', 'listing.discounts']);

        return response()->json([
            'message' => 'Transaction details retrieved.',
            'data' => $transaction,
        ]);
    }

    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->payment_status !== 'unpaid') {
            return response()->json([
                'message' => 'Payment status is already marked as paid.',
            ], 400);
        }

        $transaction->payment_status = 'paid';
        $transaction->save();

        return response()->json([
            'message' => 'Payment status updated to paid.',
            'transaction' => $transaction,
        ], 200);
    }
}
