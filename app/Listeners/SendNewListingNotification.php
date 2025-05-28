<?php

namespace App\Listeners;

use App\Events\ListingCreated;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class SendNewListingNotification
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
    public function handle(ListingCreated $event): void
    {
        $listing = $event->listing;

        // Only send notification for published listings
        if ($listing->status !== 'published') {
            return;
        }

        try {
            if (!$listing->user) {
                Log::info('No listing owner found for new listing notification');
                return;
            }
            // Use the helper method to check notification preferences
            if (!NotificationHelper::shouldSendNotification($listing->user, 'new_listings')) {
                Log::info("New listing notification skipped for user ID: {$listing->user->id} due to preferences");
                return;
            }
            NotificationHelper::sendListingNotification(
                $listing->id,
                'New Listing Available',
                "{$listing->title} is now available",
                'new_listing',
                [
                    'listing_id' => $listing->id,
                    'category_id' => $listing->category_id,
                    'price' => $listing->price,
                    'action' => 'view_listing',
                    'related_id' => $listing->id
                ],
                $listing->images[0] ?? null // Use first image if available
            );

            Log::info("New listing notification sent for listing ID: {$listing->id}");
        } catch (\Exception $e) {
            Log::error('Failed to send new listing notification: ' . $e->getMessage());
        }
    }
}
