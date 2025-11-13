<?php
// app/Notifications/ActivateAccount.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ActivateAccountNotification extends Notification
{
    use Queueable;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $activationUrl = env('FRONTEND_URL') . "/activate-account?token={$this->user->activation_token}&email={$this->user->email}";

        return (new MailMessage)
            ->subject('Activate Your Account')
            ->greeting("Hello, {$this->user->email}")
            ->line('Please activate your account by clicking the button below.')
            ->action('Activate Account', $activationUrl)
            ->line('After activation, you can set your password and complete your profile.')
            ->salutation("Regards,\n" . env('MAIL_FROM_NAME'));
    }
}
