<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use Illuminate\Notifications\Notification;

class SupportRequestSubmitted extends Notification
{
    public function __construct(public SupportRequest $supportRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{support_request_id: int, title: string, subject: string, vendor_name: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'support_request_id' => $this->supportRequest->id,
            'title' => 'New support request',
            'subject' => $this->supportRequest->subject,
            'vendor_name' => $this->supportRequest->vendor->name,
        ];
    }
}
