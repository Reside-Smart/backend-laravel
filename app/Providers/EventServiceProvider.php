<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\TransactionCreated;
use App\Events\ListingCreated;
use App\Events\DiscountCreated;
use App\Events\ReviewCreated;
use App\Listeners\SendTransactionNotification;
use App\Listeners\SendNewListingNotification;
use App\Listeners\SendDiscountNotification;
use App\Listeners\SendReviewNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TransactionCreated::class => [
            SendTransactionNotification::class,
        ],
        ListingCreated::class => [
            SendNewListingNotification::class,
        ],
        DiscountCreated::class => [
            SendDiscountNotification::class,
        ],
        ReviewCreated::class => [
            SendReviewNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
