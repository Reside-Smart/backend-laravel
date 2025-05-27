<?php

namespace App\Helpers;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Send notification to a single user
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $type
     * @param string|null $image
     * @return bool
     */
    public static function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general',
        ?string $image = null
    ) {
        // Check if user has enabled this notification type
        $settings = $user->notificationSettings;
        $settingKey = $type . '_notifications';

        if ($settings && isset($settings->$settingKey) && $settings->$settingKey === false) {
            return false; // User has disabled this notification type
        }

        try {
            $user->notify(new AppNotification($title, $body, $data, $type, $image));
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple users
     *
     * @param array|Collection $users
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $type
     * @param string|null $image
     * @return array
     */
    public static function sendToUsers($users, string $title, string $body, array $data = [], string $type = 'general', ?string $image = null)
    {
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $result = self::sendToUser($user, $title, $body, $data, $type, $image);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($users)
        ];
    }

    /**
     * Send notification to a topic
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $image
     * @return bool
     */
    public static function sendToTopic(string $topic, string $title, string $body, array $data = [], ?string $image = null)
    {
        $fcmService = app(FcmService::class);

        $data = array_merge($data, [
            'notification_type' => 'topic',
            'topic' => $topic,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $result = $fcmService->sendToTopic($topic, $title, $body, $data, $image);

        return $result['success'];
    }

    /**
     * Send a notification to all users
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $type
     * @param string|null $image
     * @return array
     */
    public static function sendToAllUsers(string $title, string $body, array $data = [], string $type = 'general', ?string $image = null)
    {
        // Get all users who have the specific notification type enabled
        $settingKey = $type . '_notifications';

        $users = User::whereHas('notificationSettings', function ($query) use ($settingKey) {
            $query->where($settingKey, true);
        })->get();

        return self::sendToUsers($users, $title, $body, $data, $type, $image);
    }

    /**
     * Send a notification about a specific listing
     *
     * @param int $listingId
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array $extraData
     * @param string|null $image
     * @return bool
     */
    public static function sendListingNotification(int $listingId, string $title, string $body, string $type = 'new_listing', array $extraData = [], ?string $image = null)
    {
        $data = array_merge($extraData, [
            'listing_id' => $listingId,
            'action' => 'view_listing'
        ]);

        // If the notification is about a new listing, use topic
        if ($type === 'new_listing') {
            return self::sendToTopic('new_listings', $title, $body, $data, $image);
        }

        // Otherwise, we might need more specific targeting
        return false;
    }
}
