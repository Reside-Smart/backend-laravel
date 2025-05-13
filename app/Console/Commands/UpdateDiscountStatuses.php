<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ListingDiscount;
use Carbon\Carbon;

class UpdateDiscountStatuses extends Command
{
    protected $signature = 'discounts:update-statuses';
    protected $description = 'Activate and expire discounts based on dates';

    public function handle()
    {
        $today = Carbon::today();


        ListingDiscount::where('status', 'inactive')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->update(['status' => 'active']);

        ListingDiscount::where('status', '!=', 'expired')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'expired']);

        $this->info('Discount statuses updated successfully!');
    }
}
