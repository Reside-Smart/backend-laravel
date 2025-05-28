<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Rating;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\ListingDiscount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function userOverview()
    {
        $user = Auth::user();

        // Property statistics
        $totalListings = Listing::where('user_id', $user->id)->count();
        $activeListings = Listing::where('user_id', $user->id)
            ->where('status', 'published')
            ->where('is_available', 1)
            ->count();

        $rentListings = Listing::where('user_id', $user->id)
            ->where('type', 'rent')
            ->count();

        $sellListings = Listing::where('user_id', $user->id)
            ->where('type', 'sell')
            ->count();

        // Transaction statistics
        $totalTransactions = Transaction::where('seller_id', $user->id)->count();
        $pendingTransactions = Transaction::where('seller_id', $user->id)
            ->where('payment_status', 'unpaid')
            ->count();

        $totalRevenue = Transaction::where('seller_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('amount_paid');

        // Rating and review statistics
        $averageRating = Rating::whereIn('listing_id', function ($query) use ($user) {
            $query->select('id')
                ->from('listings')
                ->where('user_id', $user->id);
        })->avg('rating');

        $totalReviews = Review::whereHas('listing', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        // Purchases
        $totalPurchases = Transaction::where('buyer_id', $user->id)->count();
        $totalSpent = Transaction::where('buyer_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('amount_paid');

        return response()->json([
            'listings_stats' => [
                'total' => $totalListings,
                'active' => $activeListings,
                'rent' => $rentListings,
                'sell' => $sellListings,
            ],
            'transaction_stats' => [
                'total_sales' => $totalTransactions,
                'pending_payments' => $pendingTransactions,
                'total_revenue' => round($totalRevenue, 2),
                'total_purchases' => $totalPurchases,
                'total_spent' => round($totalSpent, 2),
            ],
            'feedback_stats' => [
                'average_rating' => round($averageRating ?? 0, 1),
                'total_reviews' => $totalReviews,
            ],
        ]);
    }

    public function revenueOverTime(Request $request)
    {
        $user = Auth::user();
        $timeframe = $request->query('timeframe', 'month'); // Options: week, month, year

        $startDate = Carbon::now();
        $groupFormat = '%Y-%m-%d'; // Default daily grouping

        // Set start date and format based on timeframe
        if ($timeframe === 'week') {
            $startDate = $startDate->subDays(7);
        } elseif ($timeframe === 'month') {
            $startDate = $startDate->subDays(30);
        } elseif ($timeframe === 'year') {
            $startDate = $startDate->subMonths(12);
            $groupFormat = '%Y-%m';
        }

        $revenues = Transaction::where('seller_id', $user->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$groupFormat') as date"),
                DB::raw('SUM(amount_paid) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates with zero revenue
        $filledData = [];
        $currentDate = clone $startDate;
        $endDate = Carbon::now();

        if ($timeframe === 'year') {
            // Monthly intervals for yearly view
            while ($currentDate <= $endDate) {
                $dateKey = $currentDate->format('Y-m');
                $filledData[$dateKey] = 0;
                $currentDate->addMonth();
            }
        } else {
            // Daily intervals for weekly and monthly views
            while ($currentDate <= $endDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $filledData[$dateKey] = 0;
                $currentDate->addDay();
            }
        }

        // Add actual revenue data
        foreach ($revenues as $entry) {
            $filledData[$entry->date] = round($entry->revenue, 2);
        }

        return response()->json([
            'timeframe' => $timeframe,
            'data' => [
                'labels' => array_keys($filledData),
                'values' => array_values($filledData),
            ],
        ]);
    }

    public function listingPerformance()
    {
        $user = Auth::user();

        $listings = Listing::where('user_id', $user->id)
            ->where('status', 'published')
            ->withCount(['transactions', 'favoritedBy as favorites_count', 'reviews'])
            ->get()
            ->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'name' => $listing->name,
                    'type' => $listing->type,
                    'transactions_count' => $listing->transactions_count,
                    'favorites_count' => $listing->favorites_count,
                    'reviews_count' => $listing->reviews_count,
                    'average_rating' => round($listing->average_reviews ?? 0, 1),
                    'created_at' => $listing->created_at->format('Y-m-d'),
                    'view_efficiency' => $listing->favorites_count > 0 ?
                        round(($listing->transactions_count / $listing->favorites_count) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('transactions_count')
            ->values();

        $topPerformers = $listings->take(5);

        // Calculate average metrics
        $avgTransactions = $listings->avg('transactions_count');
        $avgFavorites = $listings->avg('favorites_count');
        $avgRating = $listings->avg('average_rating');

        return response()->json([
            'top_performers' => $topPerformers,
            'averages' => [
                'transactions' => round($avgTransactions, 1),
                'favorites' => round($avgFavorites, 1),
                'rating' => round($avgRating, 1),
            ],
        ]);
    }

    public function activityBreakdown()
    {
        $user = Auth::user();
        $lastMonth = Carbon::now()->subDays(30);

        // As seller
        $sellerTransactions = Transaction::where('seller_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        $newListings = Listing::where('user_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        $updatedListings = Listing::where('user_id', $user->id)
            ->where('updated_at', '>=', $lastMonth)
            ->where('created_at', '<', $lastMonth)
            ->count();

        $receivedReviews = Review::whereHas('listing', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('created_at', '>=', $lastMonth)
            ->count();

        // As buyer
        $buyerTransactions = Transaction::where('buyer_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        $postedReviews = Review::where('user_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        $postedRatings = Rating::where('user_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        $newFavorites = DB::table('favorites')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        return response()->json([
            'period' => 'last_30_days',
            'as_seller' => [
                'sales' => $sellerTransactions,
                'new_listings' => $newListings,
                'updated_listings' => $updatedListings,
                'received_reviews' => $receivedReviews,
            ],
            'as_buyer' => [
                'purchases' => $buyerTransactions,
                'reviews_posted' => $postedReviews,
                'ratings_posted' => $postedRatings,
                'properties_favorited' => $newFavorites,
            ],
        ]);
    }

    public function categoryDistribution()
    {
        $user = Auth::user();

        $categoryDistribution = Listing::where('user_id', $user->id)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'count' => $item->count,
                ];
            });

        $rentTypeDistribution = Listing::where('user_id', $user->id)
            ->where('type', 'rent')
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'count' => $item->count,
                ];
            });

        $sellTypeDistribution = Listing::where('user_id', $user->id)
            ->where('type', 'sell')
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'all_listings' => $categoryDistribution,
            'rent_listings' => $rentTypeDistribution,
            'sell_listings' => $sellTypeDistribution,
        ]);
    }

    public function transactionAnalysis()
    {
        $user = Auth::user();

        // Analyze seller transactions
        $sellerTransactions = Transaction::where('seller_id', $user->id)
            ->get();

        $transactionTypeCounts = $sellerTransactions
            ->groupBy('transaction_type')
            ->map(function ($group) {
                return count($group);
            });

        $paymentMethodCounts = $sellerTransactions
            ->groupBy('payment_method')
            ->map(function ($group) {
                return count($group);
            });

        $paymentStatusCounts = $sellerTransactions
            ->groupBy('payment_status')
            ->map(function ($group) {
                return count($group);
            });

        // Get revenue breakdown
        $revenueByType = Transaction::where('seller_id', $user->id)
            ->where('payment_status', 'paid')
            ->select('transaction_type', DB::raw('SUM(amount_paid) as total'))
            ->groupBy('transaction_type')
            ->get()
            ->pluck('total', 'transaction_type')
            ->toArray();

        // Analyze the days of week when transactions occur most
        $transactionsByDayOfWeek = Transaction::where('seller_id', $user->id)
            ->select(DB::raw('DAYNAME(created_at) as day_name'), DB::raw('COUNT(*) as count'))
            ->groupBy('day_name')
            ->orderByRaw('FIELD(day_name, "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday")')
            ->get()
            ->pluck('count', 'day_name')
            ->toArray();

        // Fill in missing days with zero count
        $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($daysOfWeek as $day) {
            if (!isset($transactionsByDayOfWeek[$day])) {
                $transactionsByDayOfWeek[$day] = 0;
            }
        }

        // Sort by days of week
        $orderedTransactionsByDay = [];
        foreach ($daysOfWeek as $day) {
            $orderedTransactionsByDay[$day] = $transactionsByDayOfWeek[$day];
        }

        return response()->json([
            'transaction_types' => $transactionTypeCounts,
            'payment_methods' => $paymentMethodCounts,
            'payment_statuses' => $paymentStatusCounts,
            'revenue_by_type' => array_map(function ($amount) {
                return round($amount, 2);
            }, $revenueByType),
            'transactions_by_day' => $orderedTransactionsByDay,
        ]);
    }

    public function spendingAnalysis()
    {
        $user = Auth::user();

        // Monthly spending over the past year
        $lastYear = Carbon::now()->subYear();

        $monthlySpending = Transaction::where('buyer_id', $user->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $lastYear)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount_paid) as total_spent')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Transform to a more readable format
        $formattedSpending = [];
        foreach ($monthlySpending as $record) {
            $date = Carbon::createFromDate($record->year, $record->month, 1)->format('Y-m');
            $formattedSpending[$date] = round($record->total_spent, 2);
        }

        // Fill in missing months with zero
        $filledMonthlySpending = [];
        $currentDate = clone $lastYear;
        $endDate = Carbon::now();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m');
            $filledMonthlySpending[$dateKey] = $formattedSpending[$dateKey] ?? 0;
            $currentDate->addMonth();
        }

        // Spending by property type
        $spendingByType = Transaction::where('buyer_id', $user->id)
            ->where('payment_status', 'paid')
            ->join('listings', 'transactions.listing_id', '=', 'listings.id')
            ->select('listings.type', DB::raw('SUM(transactions.amount_paid) as total'))
            ->groupBy('listings.type')
            ->get()
            ->pluck('total', 'type')
            ->toArray();

        // Spending by category
        $spendingByCategory = Transaction::where('buyer_id', $user->id)
            ->where('payment_status', 'paid')
            ->join('listings', 'transactions.listing_id', '=', 'listings.id')
            ->join('categories', 'listings.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transactions.amount_paid) as total'))
            ->groupBy('category_name')
            ->get()
            ->pluck('total', 'category_name')
            ->toArray();

        return response()->json([
            'monthly_spending' => [
                'labels' => array_keys($filledMonthlySpending),
                'values' => array_values($filledMonthlySpending),
            ],
            'spending_by_type' => array_map(function ($amount) {
                return round($amount, 2);
            }, $spendingByType),
            'spending_by_category' => array_map(function ($amount) {
                return round($amount, 2);
            }, $spendingByCategory),
        ]);
    }

    public function rentalPerformance()
    {
        $user = Auth::user();

        // Get all rental listings
        $rentalListings = Listing::where('user_id', $user->id)
            ->where('type', 'rent')
            ->where('status', 'published')
            ->with(['rentalOptions' => function ($query) {
                $query->where('is_cancelled', 0);
            }])
            ->get();

        // Analyze rental option popularity
        $optionPopularity = [];
        $unitTypes = ['Day', 'Week', 'Month', 'Year'];

        foreach ($unitTypes as $unit) {
            $transactions = Transaction::whereIn('listing_id', $rentalListings->pluck('id'))
                ->where('transaction_type', 'rent')
                ->whereHas('rentalOption', function ($query) use ($unit) {
                    $query->where('unit', $unit);
                })
                ->count();

            $optionPopularity[$unit] = $transactions;
        }

        // Analyze average occupancy rate
        $rentalTransactions = Transaction::whereIn('listing_id', $rentalListings->pluck('id'))
            ->where('transaction_type', 'rent')
            ->get();

        $occupancyData = [];

        // Calculate occupancy for the past 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = (clone $month)->startOfMonth();
            $monthEnd = (clone $month)->endOfMonth();
            $daysInMonth = $monthEnd->day;
            $monthLabel = $month->format('M Y');

            // Initialize occupancy days for each listing
            $listingOccupancy = [];
            foreach ($rentalListings as $listing) {
                $listingOccupancy[$listing->id] = 0;
            }

            // Calculate occupied days for each listing in this month
            foreach ($rentalTransactions as $transaction) {
                $checkIn = Carbon::parse($transaction->check_in_date);
                $checkOut = Carbon::parse($transaction->check_out_date);

                // Skip if transaction is outside this month
                if ($checkOut < $monthStart || $checkIn > $monthEnd) {
                    continue;
                }

                // Calculate overlap with this month
                $effectiveStart = $checkIn->lt($monthStart) ? $monthStart : $checkIn;
                $effectiveEnd = $checkOut->gt($monthEnd) ? $monthEnd : $checkOut;
                $occupiedDays = $effectiveEnd->diffInDays($effectiveStart) + 1;

                if (isset($listingOccupancy[$transaction->listing_id])) {
                    $listingOccupancy[$transaction->listing_id] += $occupiedDays;
                }
            }

            // Calculate average occupancy rate
            $totalRate = 0;
            $activeListings = 0;

            foreach ($listingOccupancy as $listingId => $occupiedDays) {
                // Only consider listings that existed during this month
                $listing = $rentalListings->firstWhere('id', $listingId);
                if ($listing && $listing->created_at <= $monthEnd) {
                    $rate = min(100, ($occupiedDays / $daysInMonth) * 100);
                    $totalRate += $rate;
                    $activeListings++;
                }
            }

            $avgRate = $activeListings > 0 ? $totalRate / $activeListings : 0;
            $occupancyData[$monthLabel] = round($avgRate, 1);
        }

        // Average revenue per rental duration
        $revenueByDuration = [];

        foreach ($unitTypes as $unit) {
            $totalRevenue = Transaction::whereIn('listing_id', $rentalListings->pluck('id'))
                ->where('transaction_type', 'rent')
                ->where('payment_status', 'paid')
                ->whereHas('rentalOption', function ($query) use ($unit) {
                    $query->where('unit', $unit);
                })
                ->sum('amount_paid');

            $transactionCount = Transaction::whereIn('listing_id', $rentalListings->pluck('id'))
                ->where('transaction_type', 'rent')
                ->where('payment_status', 'paid')
                ->whereHas('rentalOption', function ($query) use ($unit) {
                    $query->where('unit', $unit);
                })
                ->count();

            $avgRevenue = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
            $revenueByDuration[$unit] = round($avgRevenue, 2);
        }

        return response()->json([
            'rental_unit_popularity' => $optionPopularity,
            'monthly_occupancy_rate' => $occupancyData,
            'avg_revenue_by_duration' => $revenueByDuration,
        ]);
    }

    public function reviewAnalytics()
    {
        $user = Auth::user();

        // Get listings and their reviews
        $listings = Listing::where('user_id', $user->id)
            ->withCount(['reviews'])
            ->with(['reviews' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get();

        // Rating distribution
        $ratingDistribution = Rating::whereIn('listing_id', $listings->pluck('id'))
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->pluck('count', 'rating')
            ->toArray();

        // Ensure all ratings from 1-5 are represented
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratingDistribution[$i])) {
                $ratingDistribution[$i] = 0;
            }
        }
        ksort($ratingDistribution); // Sort by rating

        // Calculate average rating over time (monthly)
        $monthlyRatings = Rating::whereIn('listing_id', $listings->pluck('id'))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months
        $ratingsByMonth = [];
        $currentMonth = Carbon::now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $monthKey = $currentMonth->format('Y-m');
            $foundMonth = $monthlyRatings->firstWhere('month', $monthKey);

            $ratingsByMonth[$monthKey] = [
                'avg_rating' => $foundMonth ? round($foundMonth->avg_rating, 1) : 0,
                'count' => $foundMonth ? $foundMonth->count : 0,
            ];

            $currentMonth->addMonth();
        }

        // Most recent reviews and their ratings
        $recentReviews = Review::whereIn('listing_id', $listings->pluck('id'))
            ->with(['user:id,name,image', 'listing:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'listing_name' => $review->listing->name,
                    'user_name' => $review->user->name,
                    'user_image' => $review->user->image,
                    'text' => $review->text,
                    'created_at' => $review->created_at->format('Y-m-d'),
                ];
            });

        // Count of reviews by listing
        $reviewsByListing = $listings->map(function ($listing) {
            return [
                'listing_name' => $listing->name,
                'review_count' => $listing->reviews_count,
                'average_rating' => round($listing->average_rating ?? 0, 1),
            ];
        })->sortByDesc('review_count')->values()->take(5);

        return response()->json([
            'rating_distribution' => $ratingDistribution,
            'monthly_ratings' => $ratingsByMonth,
            'recent_reviews' => $recentReviews,
            'top_reviewed_listings' => $reviewsByListing,
        ]);
    }

    public function discountPerformance()
    {
        $user = Auth::user();

        // Get user's listings
        $listingIds = Listing::where('user_id', $user->id)->pluck('id');

        // Get discounts and their transaction count
        $discounts = ListingDiscount::whereIn('listing_id', $listingIds)
            ->withCount(['transactions'])
            ->get();

        $activeDiscounts = $discounts->where('status', 'active')->count();
        $inactiveDiscounts = $discounts->where('status', 'inactive')->count();
        $expiredDiscounts = $discounts->where('status', 'expired')->count();

        // Average discount percentage
        $avgDiscount = $discounts->avg('percentage');

        // Transactions with discounts vs without
        $transactionsWithDiscount = Transaction::whereIn('listing_id', $listingIds)
            ->whereNotNull('listing_discount_id')
            ->count();

        $transactionsWithoutDiscount = Transaction::whereIn('listing_id', $listingIds)
            ->whereNull('listing_discount_id')
            ->count();

        // Revenue comparison
        $revenueWithDiscount = Transaction::whereIn('listing_id', $listingIds)
            ->whereNotNull('listing_discount_id')
            ->where('payment_status', 'paid')
            ->sum('amount_paid');

        $revenueWithoutDiscount = Transaction::whereIn('listing_id', $listingIds)
            ->whereNull('listing_discount_id')
            ->where('payment_status', 'paid')
            ->sum('amount_paid');

        // Most effective discounts (transactions per discount)
        $effectiveDiscounts = $discounts
            ->where('transactions_count', '>', 0)
            ->sortByDesc('transactions_count')
            ->values()
            ->take(5)
            ->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'percentage' => $discount->percentage,
                    'transactions' => $discount->transactions_count,
                    'start_date' => Carbon::parse($discount->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::parse($discount->end_date)->format('Y-m-d'),
                    'status' => $discount->status,
                ];
            });

        return response()->json([
            'discount_stats' => [
                'active' => $activeDiscounts,
                'inactive' => $inactiveDiscounts,
                'expired' => $expiredDiscounts,
                'avg_percentage' => round($avgDiscount, 1),
            ],
            'transaction_comparison' => [
                'with_discount' => $transactionsWithDiscount,
                'without_discount' => $transactionsWithoutDiscount,
            ],
            'revenue_comparison' => [
                'with_discount' => round($revenueWithDiscount, 2),
                'without_discount' => round($revenueWithoutDiscount, 2),
            ],
            'top_performing_discounts' => $effectiveDiscounts,
        ]);
    }
}
