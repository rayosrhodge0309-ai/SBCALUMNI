<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationReplied extends Notification
{
    use Queueable;

    public function __construct(private readonly EventRegistration $eventRegistration)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->eventRegistration->event;
        $name = $notifiable->name ?? $this->eventRegistration->attendee_name ?? 'Alumni student';
        $statusLabel = $this->eventRegistration->status_label;
        $headline = $this->eventRegistration->status === EventRegistration::STATUS_REGISTERED
            ? "You're already registered to event: ".$event?->title.'.'
            : 'Your registration for '.$event?->title.' is now '.$statusLabel.'.';

        return (new MailMessage)
            ->subject('Event Registration Update')
            ->greeting('Hello '.$name.',')
            ->line($headline)
            ->line('Status: '.$statusLabel)
            ->line('Event Date: '.$event?->event_date?->format('F d, Y'))
            ->when(
                filled($event?->location),
                fn (MailMessage $message): MailMessage => $message->line('Location: '.$event->location)
            )
            ->when(
                filled($this->eventRegistration->admin_reply),
                fn (MailMessage $message): MailMessage => $message->line('Admin Reply: '.$this->eventRegistration->admin_reply)
            )
            ->action('Open Alumni Dashboard', route('portal.dashboard'))
            ->line('Please sign in to your Alumni Portal dashboard for the latest event registration status.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $event = $this->eventRegistration->event;
        $statusLabel = $this->eventRegistration->status_label;

        return [
            'kind' => 'event_registration_replied',
            'event_registration_id' => $this->eventRegistration->id,
            'event_id' => $event?->id,
            'title' => 'Event registration updated',
            'message' => 'Your registration for '.$event?->title.' is now '.$statusLabel.'.',
            'event_title' => $event?->title,
            'event_date' => $event?->event_date?->format('F d, Y'),
            'status' => $statusLabel,
            'admin_reply' => $this->eventRegistration->admin_reply,
            'url' => route('portal.dashboard'),
        ];
    }
}
