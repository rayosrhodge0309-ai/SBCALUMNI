<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlumniAccountApproved extends Notification
{
    use Queueable;

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
            ->subject('Your Alumni Portal Account Has Been Approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your alumni portal account has been approved by the administrator.')
            ->line('You may now sign in to the Alumni Portal to request school records and view updates.')
            ->action('Login to Alumni Portal', route('portal.login'))
            ->line('Thank you for staying connected with St. Bridget College Batangas.');
    }
}
