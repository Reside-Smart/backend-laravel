<?php

use Illuminate\Foundation\Inspiring;
use App\Models\Listing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ListingDiscount;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    $today = Carbon::today();

    ListingDiscount::where('status', 'inactive')
        ->whereDate('start_date', '<=', $today)
        ->whereDate('end_date', '>=', $today)
        ->update(['status' => 'active']);

    ListingDiscount::where('status', '!=', 'expired')
        ->whereDate('end_date', '<', $today)
        ->update(['status' => 'expired']);
})->hourly();
