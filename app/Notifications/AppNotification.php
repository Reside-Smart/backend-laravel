<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;
    protected $type;
    protected $image;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $body,
        array $data = [],
        string $type = 'general',
        ?string $image = null
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->data = array_merge($data, [
            'notification_type' => $type,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);
        $this->type = $type;
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    /**
     * Get the firebase cloud messaging representation of the notification.
     */
    public function toFcm($notifiable)
    {
        $fcmService = app(FcmService::class);
        $tokens = $notifiable->routeNotificationForFcm($this);

        if (empty($tokens)) {
            return null;
        }

        // For a single token
        if (count($tokens) === 1) {
            return $fcmService->sendToDevice(
                $tokens[0],
                $this->title,
                $this->body,
                $this->data,
                $this->image
            );
        }

        // For multiple tokens
        return $fcmService->sendToMultipleDevices(
            $tokens,
            $this->title,
            $this->body,
            $this->data,
            $this->image
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'data' => $this->data,
            'image' => $this->image,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
