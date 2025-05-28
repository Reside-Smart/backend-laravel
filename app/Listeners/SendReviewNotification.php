<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class SendReviewNotification
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
    public function handle(ReviewCreated $event): void
    {
        $review = $event->review;
        $listing = $review->listing;
        $listingOwner = $listing->user;

        try {
            // Check if user exists
            if (!$listingOwner) {
                Log::info('No listing owner found for review notification');
                return;
            }

            // Use the helper method to check notification preferences
            if (NotificationHelper::shouldSendNotification($listingOwner, 'reviews')) {
                NotificationHelper::sendToUser(
                    $listingOwner,
                    'New Review',
                    "{$review->user->name} left a review on your listing: {$listing->name}",
                    [
                        'listing_id' => $listing->id,
                        'review_id' => $review->id,
                        'action' => 'view_review',
                        'related_id' => $listing->id
                    ],
                    'reviews'
                );

                Log::info("Review notification sent to user ID: {$listingOwner->id}");
            } else {
                Log::info("Review notification skipped for user ID: {$listingOwner->id} due to preferences");
            }
        } catch (\Exception $e) {
            Log::error('Failed to send review notification: ' . $e->getMessage());
        }
    }
}
