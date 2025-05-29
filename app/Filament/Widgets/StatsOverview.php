<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected function getStats(): array
    {
        // Users stats
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $usersPercentChange = $this->calculatePercentChange(
            User::where('created_at', '>=', Carbon::now()->subMonths(2)->startOfMonth())
                ->where('created_at', '<', Carbon::now()->startOfMonth())
                ->count(),
            $newUsersThisMonth
        );

        // Listings stats
        $totalListings = Listing::count();
        $newListingsThisMonth = Listing::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $listingsPercentChange = $this->calculatePercentChange(
            Listing::where('created_at', '>=', Carbon::now()->subMonths(2)->startOfMonth())
                ->where('created_at', '<', Carbon::now()->startOfMonth())
                ->count(),
            $newListingsThisMonth
        );

        // Transaction stats
        $totalRevenue = Transaction::where('payment_status', 'paid')->sum('amount_paid');
        $revenueThisMonth = Transaction::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount_paid');
        $revenuePercentChange = $this->calculatePercentChange(
            Transaction::where('payment_status', 'paid')
                ->where('created_at', '>=', Carbon::now()->subMonths(2)->startOfMonth())
                ->where('created_at', '<', Carbon::now()->startOfMonth())
                ->sum('amount_paid'),
            $revenueThisMonth
        );

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($newUsersThisMonth . ' new users this month')
                ->descriptionIcon($usersPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($usersPercentChange >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->icon('heroicon-o-users'),

            Stat::make('Total Properties', $totalListings)
                ->description($newListingsThisMonth . ' new properties this month')
                ->descriptionIcon($listingsPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($listingsPercentChange >= 0 ? 'success' : 'danger')
                ->chart([3, 5, 7, 6, 8, 10, 15])
                ->icon('heroicon-o-home'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('$' . number_format($revenueThisMonth, 2) . ' this month')
                ->descriptionIcon($revenuePercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenuePercentChange >= 0 ? 'success' : 'danger')
                ->chart([4, 8, 7, 12, 11, 16, 20])
                ->icon('heroicon-o-banknotes'),
        ];
    }

    private function calculatePercentChange($previous, $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
