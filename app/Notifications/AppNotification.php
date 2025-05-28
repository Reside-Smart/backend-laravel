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
     * This should only return the DATA, not send the notification
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'image' => $this->image
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'data' => $this->data,
            'image' => $this->image,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
