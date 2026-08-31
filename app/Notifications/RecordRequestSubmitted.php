<?php

namespace App\Notifications;

use App\Models\RecordRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(private readonly RecordRequest $recordRequest)
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
        $alumnus = $this->recordRequest->alumni;
        $alumniName = $alumnus?->full_name ?? 'An alumni student';
        $studentId = $alumnus?->student_id_display ?? 'No student ID';

        return (new MailMessage)
            ->subject('New Alumni Record Request Submitted')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($alumniName.' submitted a new alumni record request.')
            ->line('Student ID: '.$studentId)
            ->line('Request: '.$this->recordRequest->request_type)
            ->line('Record Year: '.$this->recordRequest->year_requested)
            ->when(
                filled($this->recordRequest->requester_note),
                fn (MailMessage $message): MailMessage => $message->line('Alumni Message: '.$this->recordRequest->requester_note)
            )
            ->action('Open Record Requests', route('requests.index'))
            ->line('Please review this request in the admin dashboard.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $alumnus = $this->recordRequest->alumni;
        $alumniName = $alumnus?->full_name ?? 'An alumni student';

        return [
            'kind' => 'record_request_submitted',
            'request_id' => $this->recordRequest->id,
            'title' => 'New record request',
            'message' => $alumniName.' submitted a '.$this->recordRequest->request_type.' request.',
            'request_type' => $this->recordRequest->request_type,
            'year_requested' => $this->recordRequest->year_requested,
            'requester_note' => $this->recordRequest->requester_note,
            'alumni_name' => $alumniName,
            'url' => route('requests.index'),
        ];
    }
}
