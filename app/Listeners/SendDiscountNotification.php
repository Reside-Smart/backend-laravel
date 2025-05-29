<?php

namespace App\Listeners;

use App\Events\DiscountCreated;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class SendDiscountNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DiscountCreated $event): void
    {
        $discount = $event->discount;
        $listing = $discount->listing;

        try {
            // Send to users who have favorited this listing
            $users = $listing->favoriteUsers;

            foreach ($users as $user) {
                // Use the helper method to check notification preferences
                if (NotificationHelper::shouldSendNotification($user, 'discounts')) {
                    NotificationHelper::sendToUser(
                        $user,
                        'New Discount Available',
                        "{$discount->name}: {$discount->percentage}% off on {$listing->title}",
                        [
                            'listing_id' => $listing->id,
                            'discount_id' => $discount->id,
                            'action' => 'view_listing'
                        ],
                        'discounts'
                    );

                    Log::info("Discount notification sent to user ID: {$user->id}");
                }
            }

            // Also send to discounts topic
            NotificationHelper::sendToTopic(
                'discounts',
                'New Discount Available',
                "{$discount->percentage}% off on {$listing->title}",
                [
                    'listing_id' => $listing->id,
                    'discount_id' => $discount->id,
                    'action' => 'view_listing',
                    'related_id' => $listing->id
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send discount notification: ' . $e->getMessage());
        }
    }
}
