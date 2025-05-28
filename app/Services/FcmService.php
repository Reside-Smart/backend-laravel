<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\MulticastMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;

class FcmService
{
    protected $messaging;
    protected $projectId;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(env('FIREBASE_CREDENTIALS'));

            $this->messaging = $factory->createMessaging();
            $this->projectId = env('FIREBASE_PROJECT_ID');
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to a single device
     * 
     * @param string $token FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @param string|null $imageUrl Optional image URL
     * @return array Response with status and message
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null)
    {
        if (!$this->messaging) {
            return ['success' => false, 'message' => 'Firebase messaging not initialized'];
        }

        try {
            // Create notification
            $notification = FirebaseNotification::create($title, $body);

            if ($imageUrl) {
                $notification = $notification->withImageUrl($imageUrl);
            }

            // Create message
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            // Add platform-specific configurations
            $message = $this->addPlatformConfigs($message);

            // Send message
            $result = $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'result' => $result
            ];
        } catch (FirebaseException $e) {
            $this->handleFirebaseError($e, $token);
            return [
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to multiple devices
     * 
     * @param array $tokens Array of FCM device tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @param string|null $imageUrl Optional image URL
     * @return array Response with status and message
     */
    public function sendToMultipleDevices(array $tokens, string $title, string $body, array $data = [], ?string $imageUrl = null)
    {
        if (!$this->messaging || empty($tokens)) {
            return ['success' => false, 'message' => 'Firebase messaging not initialized or no tokens provided'];
        }

        try {
            // Create notification
            $notification = FirebaseNotification::create($title, $body);

            if ($imageUrl) {
                $notification = $notification->withImageUrl($imageUrl);
            }

            // Create multicast message
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            // Add platform-specific configurations
            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ]);

            $apnsConfig = ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                        'content-available' => 1,
                    ],
                ],
            ]);

            $message = $message
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig);

            // Send to tokens
            $report = $this->messaging->sendMulticast($message, $tokens);

            // Handle failures
            $this->handleMulticastReport($report, $tokens);

            return [
                'success' => true,
                'message' => 'Notifications sent successfully',
                'successes' => $report->successes()->count(),
                'failures' => $report->failures()->count(),
            ];
        } catch (FirebaseException $e) {
            Log::error('Firebase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error sending notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to a topic
     * 
     * @param string $topic Topic name
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @param string|null $imageUrl Optional image URL
     * @return array Response with status and message
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], ?string $imageUrl = null)
    {
        if (!$this->messaging) {
            return ['success' => false, 'message' => 'Firebase messaging not initialized'];
        }

        try {
            // Create notification
            $notification = FirebaseNotification::create($title, $body);

            if ($imageUrl) {
                $notification = $notification->withImageUrl($imageUrl);
            }

            // Create message for topic
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            // Add platform-specific configurations
            $message = $this->addPlatformConfigs($message);

            // Send message
            $result = $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Topic notification sent successfully',
                'result' => $result
            ];
        } catch (FirebaseException $e) {
            Log::error('Firebase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error sending topic notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe tokens to a topic
     * 
     * @param array $tokens FCM device tokens
     * @param string $topic Topic name
     * @return array Response with status and message
     */
    public function subscribeToTopic(array $tokens, string $topic)
    {
        if (!$this->messaging || empty($tokens)) {
            return ['success' => false, 'message' => 'Firebase messaging not initialized or no tokens provided'];
        }

        try {
            $result = $this->messaging->subscribeToTopic($topic, $tokens);

            return [
                'success' => true,
                'message' => 'Successfully subscribed to topic'
            ];
        } catch (FirebaseException $e) {
            Log::error('Firebase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error subscribing to topic: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unsubscribe tokens from a topic
     * 
     * @param array $tokens FCM device tokens
     * @param string $topic Topic name
     * @return array Response with status and message
     */
    public function unsubscribeFromTopic(array $tokens, string $topic)
    {
        if (!$this->messaging || empty($tokens)) {
            return ['success' => false, 'message' => 'Firebase messaging not initialized or no tokens provided'];
        }

        try {
            $result = $this->messaging->unsubscribeFromTopic($topic, $tokens);

            return [
                'success' => true,
                'message' => 'Successfully unsubscribed from topic'
            ];
        } catch (FirebaseException $e) {
            Log::error('Firebase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error unsubscribing from topic: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate FCM token
     * 
     * @param string $token FCM device token
     * @return bool True if valid, false otherwise
     */
    public function validateToken(string $token)
    {
        if (!$this->messaging) {
            return false;
        }

        try {
            // Try to send a dry run message to validate token
            $message = CloudMessage::withTarget('token', $token);
            $this->messaging->send($message, true); // true = dry run
            return true;
        } catch (FirebaseException $e) {
            if (
                strpos($e->getMessage(), 'Invalid registration') !== false ||
                strpos($e->getMessage(), 'Not Found') !== false ||
                strpos($e->getMessage(), 'invalid-argument') !== false
            ) {
                return false;
            }
            // Other errors might be server-side, not token validity
            return true;
        } catch (\Exception $e) {
            Log::error('Error validating token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add platform specific configurations to message
     * 
     * @param CloudMessage $message
     * @return CloudMessage
     */
    private function addPlatformConfigs(CloudMessage $message)
    {
        // Android specific configuration
        $androidConfig = AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => [
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ]);

        // iOS specific configuration
        $apnsConfig = ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                    'content-available' => 1,
                    'mutable-content' => 1,
                ],
            ],
        ]);

        // Web Push specific configuration
        $webPushConfig = WebPushConfig::fromArray([
            'notification' => [
                'icon' => asset('images/logo.png'),
                'vibrate' => [100, 50, 100],
                'requireInteraction' => true,
            ],
            'fcm_options' => [
                'link' => url('/'),
            ],
        ]);

        return $message
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig)
            ->withWebPushConfig($webPushConfig);
    }

    /**
     * Handle Firebase errors for a specific token
     * 
     * @param FirebaseException $e
     * @param string $token
     * @return void
     */
    private function handleFirebaseError(FirebaseException $e, string $token)
    {
        $errorMessage = $e->getMessage();

        // Check for token-related errors
        $invalidTokenErrors = [
            'The registration token is not a valid FCM registration token',
            'The registration token is not registered',
            'Requested entity was not found',
            'invalid-argument',
            'registration-token-not-registered',
            'invalid-registration-token',
            'unregistered'
        ];

        foreach ($invalidTokenErrors as $error) {
            if (strpos($errorMessage, $error) !== false) {
                // Remove invalid token
                $this->removeInvalidTokens([$token]);
                Log::info("Removed invalid token: {$token}");
                break;
            }
        }

        Log::error('Firebase Error: ' . $errorMessage);
    }

    /**
     * Handle multicast report and remove invalid tokens
     * 
     * @param MulticastSendReport $report
     * @param array $tokens
     * @return void
     */
    private function handleMulticastReport(MulticastSendReport $report, array $tokens)
    {
        if ($report->hasFailures()) {
            $invalidTokens = [];

            foreach ($report->failures()->getItems() as $index => $failure) {
                $error = $failure->error();

                // Check if the error indicates an invalid token
                if (
                    strpos($error, 'registration-token-not-registered') !== false ||
                    strpos($error, 'invalid-argument') !== false ||
                    strpos($error, 'invalid-registration-token') !== false
                ) {
                    $invalidTokens[] = $tokens[$index];
                }

                Log::warning("FCM Multicast failure: {$error} for token {$tokens[$index]}");
            }

            // Remove invalid tokens
            if (!empty($invalidTokens)) {
                $this->removeInvalidTokens($invalidTokens);
            }
        }
    }

    /**
     * Remove invalid tokens from database
     * 
     * @param array $invalidTokens
     * @return void
     */
    public function removeInvalidTokens(array $invalidTokens)
    {
        if (!empty($invalidTokens)) {
            $count = DeviceToken::whereIn('token', $invalidTokens)->delete();
            Log::info("Removed {$count} invalid FCM tokens");
        }
    }
}
