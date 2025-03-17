<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends BaseResetPassword
{
    /**
     * Get the notification's mail representation.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Custom Password Reset Link') // Customize the subject
            ->view('emails.reset_password', [  // Use a custom view
                'actionUrl' => url(config('app.url') . route('password.reset', $this->token, false)),
                'user' => $notifiable,
            ]);
    }
}
