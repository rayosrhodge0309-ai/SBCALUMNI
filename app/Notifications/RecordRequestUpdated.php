<?php

namespace App\Notifications;

use App\Models\RecordRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordRequestUpdated extends Notification
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
        $statusLabel = RecordRequest::workflowStatuses()[$this->recordRequest->status]
            ?? ucfirst(str_replace('_', ' ', $this->recordRequest->status));

        return (new MailMessage)
            ->subject('Update on Your Alumni Record Request')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The administrator sent an update about your alumni record request.')
            ->line('Request: '.$this->recordRequest->request_type)
            ->line('Record Year: '.$this->recordRequest->year_requested)
            ->line('Status: '.$statusLabel)
            ->when(
                filled($this->recordRequest->admin_notes),
                fn (MailMessage $message): MailMessage => $message->line('Admin Note: '.$this->recordRequest->admin_notes)
            )
            ->action('Open Alumni Dashboard', route('portal.dashboard'))
            ->line('Please sign in to your Alumni Portal dashboard for the latest request status.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusLabel = RecordRequest::workflowStatuses()[$this->recordRequest->status]
            ?? ucfirst(str_replace('_', ' ', $this->recordRequest->status));

        return [
            'kind' => 'record_request_updated',
            'request_id' => $this->recordRequest->id,
            'title' => 'Request updated',
            'message' => 'Your '.$this->recordRequest->request_type.' request is now '.$statusLabel.'.',
            'request_type' => $this->recordRequest->request_type,
            'year_requested' => $this->recordRequest->year_requested,
            'status' => $statusLabel,
            'admin_notes' => $this->recordRequest->admin_notes,
            'url' => route('portal.dashboard'),
        ];
    }
}
