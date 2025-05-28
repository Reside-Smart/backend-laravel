<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Helpers\NotificationHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendTransactionNotification
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
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        try {
            // Notify buyer
            $buyer = User::find($transaction->buyer_id);
            if ($buyer && NotificationHelper::shouldSendNotification($buyer, 'transactions')) {
                NotificationHelper::sendToUser(
                    $buyer,
                    'New Transaction',
                    "Your transaction for {$transaction->listing->title} has been created",
                    [
                        'transaction_id' => $transaction->id,
                        'listing_id' => $transaction->listing_id,
                        'action' => 'view_transaction',
                        'related_id' => $transaction->id
                    ],
                    'transactions'
                );

                Log::info("Transaction notification sent to buyer ID: {$buyer->id}");
            }

            // Notify seller
            $seller = User::find($transaction->seller_id);
            if ($seller && NotificationHelper::shouldSendNotification($seller, 'transactions')) {
                NotificationHelper::sendToUser(
                    $seller,
                    'New Transaction',
                    "You have a new transaction for {$transaction->listing->title}",
                    [
                        'transaction_id' => $transaction->id,
                        'listing_id' => $transaction->listing_id,
                        'action' => 'view_transaction'
                    ],
                    'transactions'
                );

                Log::info("Transaction notification sent to seller ID: {$seller->id}");
            }
        } catch (\Exception $e) {
            Log::error('Failed to send transaction notification: ' . $e->getMessage());
        }
    }
}
