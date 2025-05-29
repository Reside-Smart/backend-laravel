<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use App\Services\FcmService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(ChannelManager::class)->extend('fcm', function ($app) {
            return new class($app->make(FcmService::class)) {
                protected $fcmService;

                public function __construct(FcmService $fcmService)
                {
                    $this->fcmService = $fcmService;
                }

                public function send($notifiable, $notification)
                {
                    $message = $notification->toFcm($notifiable);
                    $tokens = $notifiable->routeNotificationForFcm($notification);

                    if (empty($tokens)) {
                        return;
                    }

                    if (count($tokens) === 1) {
                        return $this->fcmService->sendToDevice(
                            $tokens[0],
                            $message['title'],
                            $message['body'],
                            $message['data'],
                            $message['image']
                        );
                    }

                    return $this->fcmService->sendToMultipleDevices(
                        $tokens,
                        $message['title'],
                        $message['body'],
                        $message['data'],
                        $message['image']
                    );
                }
            };
        });
    }
}
