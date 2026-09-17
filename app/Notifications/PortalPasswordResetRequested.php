<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalPasswordResetRequested extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly string $resetUrl
    )
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Alumni Portal Password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We received a request to reset your alumni portal password.')
            ->line('Your 6-digit reset code is: '.$this->token)
            ->action('Reset Password', $this->resetUrl)
            ->line('If the button does not open, return to the forgot password page and type the 6-digit reset code instead.')
            ->line('This password reset code will expire in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request this, no further action is required.');
    }
}
