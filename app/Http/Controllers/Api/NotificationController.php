<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('data->type', $request->type);
        }

        // Get all notifications with pagination
        $notifications = $query->orderBy('created_at', 'desc')->paginate(
            $request->input('per_page', 20)
        );

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Send a notification (for admin/testing purposes)
     */
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'required|string|in:transaction,new_listing,chat,discount,review,general',
            'data' => 'nullable|array',
            'image' => 'nullable|string|url',
        ]);

        $user = User::find($request->user_id);
        $settings = $user->notificationSettings;

        // Check if user has enabled this notification type
        $settingKey = $request->type . '_notifications';
        if ($settings && isset($settings->$settingKey) && $settings->$settingKey === false) {
            return response()->json(['message' => 'User has disabled this notification type'], 400);
        }

        try {
            $user->notify(new AppNotification(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type,
                $request->image
            ));

            return response()->json(['message' => 'Notification sent successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulk(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'required|string|in:transaction,new_listing,chat,discount,review,general',
            'data' => 'nullable|array',
            'image' => 'nullable|string|url',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $notifiedCount = 0;
        $skippedCount = 0;

        try {
            foreach ($users as $user) {
                $settings = $user->notificationSettings;

                // Check if user has enabled this notification type
                $settingKey = $request->type . '_notifications';
                if ($settings && isset($settings->$settingKey) && $settings->$settingKey === false) {
                    $skippedCount++;
                    continue; // Skip this user
                }

                $user->notify(new AppNotification(
                    $request->title,
                    $request->body,
                    $request->data ?? [],
                    $request->type,
                    $request->image
                ));

                $notifiedCount++;
            }

            return response()->json([
                'message' => 'Bulk notifications processed',
                'notified' => $notifiedCount,
                'skipped' => $skippedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error sending bulk notifications',
                'error' => $e->getMessage(),
                'notified' => $notifiedCount,
                'skipped' => $skippedCount
            ], 500);
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'image' => 'nullable|string|url',
        ]);

        $data = array_merge($request->data ?? [], [
            'notification_type' => 'topic',
            'topic' => $request->topic,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $result = $this->fcmService->sendToTopic(
            $request->topic,
            $request->title,
            $request->body,
            $data,
            $request->image
        );

        if ($result['success']) {
            return response()->json(['message' => 'Topic notification sent successfully']);
        }

        return response()->json([
            'message' => 'Failed to send topic notification',
            'error' => $result['message']
        ], 500);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Delete all notifications
     */
    public function destroyAll(Request $request)
    {
        $user = Auth::user();

        // Allow filtering by type
        if ($request->has('type')) {
            $user->notifications()
                ->where('data->type', $request->type)
                ->delete();

            return response()->json(['message' => 'All notifications of type "' . $request->type . '" deleted successfully']);
        }

        $user->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted successfully']);
    }

    /**
     * Test a device token by sending a test notification
     */
    public function testToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $result = $this->fcmService->sendToDevice(
            $request->token,
            'Test Notification',
            'This is a test notification from Reside Smart',
            ['test' => true]
        );

        if ($result['success']) {
            return response()->json(['message' => 'Test notification sent successfully']);
        }

        return response()->json([
            'message' => 'Failed to send test notification',
            'error' => $result['message']
        ], 500);
    }

    /**
     * Validate a device token
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $isValid = $this->fcmService->validateToken($request->token);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Token is valid' : 'Token is invalid'
        ]);
    }
}
