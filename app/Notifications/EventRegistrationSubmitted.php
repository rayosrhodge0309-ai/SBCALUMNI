<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationSubmitted extends Notification
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->eventRegistration->event;
        $alumnus = $this->eventRegistration->alumni;
        $alumniName = $this->eventRegistration->attendee_name ?: ($alumnus?->full_name ?? 'An alumni student');
        $studentId = $this->eventRegistration->attendee_student_id ?: ($alumnus?->student_id_display ?? 'No student ID');

        return (new MailMessage)
            ->subject('New Alumni Event Registration')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($alumniName.' registered for an alumni event.')
            ->line('Student ID: '.$studentId)
            ->line('Gmail: '.($this->eventRegistration->attendee_email ?: 'Not submitted'))
            ->line('Contact Number: '.($this->eventRegistration->attendee_contact_number ?: 'Not submitted'))
            ->line('Course / Strand: '.($this->eventRegistration->attendee_course ?: 'Not submitted'))
            ->line('Year Graduated: '.($this->eventRegistration->attendee_year_graduated ?: 'Not submitted'))
            ->when(
                filled($this->eventRegistration->attendee_note),
                fn (MailMessage $message): MailMessage => $message->line('Message: '.$this->eventRegistration->attendee_note)
            )
            ->line('Event: '.$event?->title)
            ->line('Event Date: '.$event?->event_date?->format('F d, Y'))
            ->when(
                filled($event?->location),
                fn (MailMessage $message): MailMessage => $message->line('Location: '.$event->location)
            )
            ->action('Open Event Registrants', route('event-registrations.index'))
            ->line('Please review and reply to the alumni student from the event registrants page.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $event = $this->eventRegistration->event;
        $alumnus = $this->eventRegistration->alumni;
        $alumniName = $this->eventRegistration->attendee_name ?: ($alumnus?->full_name ?? 'An alumni student');

        return [
            'kind' => 'event_registration_submitted',
            'event_registration_id' => $this->eventRegistration->id,
            'event_id' => $event?->id,
            'title' => 'New event registration',
            'message' => $alumniName.' registered for '.$event?->title.'.',
            'event_title' => $event?->title,
            'event_date' => $event?->event_date?->format('F d, Y'),
            'alumni_name' => $alumniName,
            'student_id' => $this->eventRegistration->attendee_student_id ?: ($alumnus?->student_id_display),
            'attendee_email' => $this->eventRegistration->attendee_email,
            'attendee_contact_number' => $this->eventRegistration->attendee_contact_number,
            'attendee_course' => $this->eventRegistration->attendee_course,
            'attendee_year_graduated' => $this->eventRegistration->attendee_year_graduated,
            'url' => route('event-registrations.index'),
        ];
    }
}
