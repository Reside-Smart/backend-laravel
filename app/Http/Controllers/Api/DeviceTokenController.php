<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\UserNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;

class DeviceTokenController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Register a new device token
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Validate token
        $isValid = $this->fcmService->validateToken($request->token);
        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid FCM token provided',
            ], 400);
        }

        // First try to update existing token if it exists
        $deviceToken = DeviceToken::updateOrCreate(
            [
                'token' => $request->token,
            ],
            [
                'user_id' => $user->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
            ]
        );

        // Make sure user has notification settings
        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'transactions' => true,
                'new_listings' => true,
                'messages' => true,
                'discounts' => true,
                'reviews' => true,
            ]
        );

        // Subscribe to topics based on user preferences
        $topics = [];
        if ($settings->transactions) {
            $topics[] = 'transactions';
        }
        if ($settings->new_listings) {
            $topics[] = 'new_listings';
        }
        if ($settings->discounts) {
            $topics[] = 'discounts';
        }

        // Subscribe to topics
        foreach ($topics as $topic) {
            $this->fcmService->subscribeToTopic([$deviceToken->token], $topic);
        }

        return response()->json([
            'message' => 'Device token registered successfully',
            'token' => $deviceToken,
        ]);
    }

    /**
     * Delete a device token
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        $deviceToken = DeviceToken::where('token', $request->token)
            ->where('user_id', $user->id)
            ->first();

        if (!$deviceToken) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        // Unsubscribe from all topics before deleting
        $topics = ['transactions', 'new_listings', 'discounts'];
        foreach ($topics as $topic) {
            $this->fcmService->unsubscribeFromTopic([$deviceToken->token], $topic);
        }

        $deviceToken->delete();
        return response()->json(['message' => 'Device token removed successfully']);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'transactions' => 'nullable|boolean',
            'new_listings' => 'nullable|boolean',
            'messages' => 'nullable|boolean',
            'discounts' => 'nullable|boolean',
            'reviews' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $settings = UserNotificationSetting::firstOrCreate(['user_id' => $user->id]);

        // Keep track of old settings to compare
        $oldSettings = $settings->toArray();

        $settings->update($request->only([
            'transactions',
            'new_listings',
            'messages',
            'discounts',
            'reviews',
        ]));

        // Update topic subscriptions based on changed settings
        $deviceTokens = $user->deviceTokens()->pluck('token')->toArray();

        if (!empty($deviceTokens)) {
            // Handle transaction notifications
            if (isset($request->transactions) && $oldSettings['transactions'] !== $settings->transactions) {
                if ($settings->transactions) {
                    $this->fcmService->subscribeToTopic($deviceTokens, 'transactions');
                } else {
                    $this->fcmService->unsubscribeFromTopic($deviceTokens, 'transactions');
                }
            }

            // Handle new listing notifications
            if (isset($request->new_listings) && $oldSettings['new_listings'] !== $settings->new_listings) {
                if ($settings->new_listings) {
                    $this->fcmService->subscribeToTopic($deviceTokens, 'new_listings');
                } else {
                    $this->fcmService->unsubscribeFromTopic($deviceTokens, 'new_listings');
                }
            }

            // Handle discount notifications
            if (isset($request->discounts) && $oldSettings['discounts'] !== $settings->discounts) {
                if ($settings->discounts) {
                    $this->fcmService->subscribeToTopic($deviceTokens, 'discounts');
                } else {
                    $this->fcmService->unsubscribeFromTopic($deviceTokens, 'discounts');
                }
            }
        }

        return response()->json([
            'message' => 'Notification settings updated',
            'settings' => $settings,
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings()
    {
        $user = Auth::user();
        $settings = UserNotificationSetting::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Get user's registered devices
     */
    public function getDevices()
    {
        $user = Auth::user();
        $devices = $user->deviceTokens;

        return response()->json([
            'devices' => $devices,
            'count' => $devices->count()
        ]);
    }
}
