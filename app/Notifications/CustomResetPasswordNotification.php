<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting("Hello {$notifiable->name},") // 👈 personal greeting
            ->line('We received a request to reset your password for your account.')
            ->line('Click the button below to reset your password.')
            ->action('Reset Password', "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->email}")
            //->line('This link will expire in 60 minutes.') // 👈 extra info
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Regards, \n'. env('MAIL_FROM_NAME'));
    }
}
