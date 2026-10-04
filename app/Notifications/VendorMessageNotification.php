<?php

namespace App\Notifications;

use App\Models\VendorSupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VendorMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public VendorSupportRequest $supportRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, message: string, support_request_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New vendor message',
            'message' => $this->supportRequest->user->name.' sent a message: '.$this->supportRequest->subject,
            'support_request_id' => $this->supportRequest->id,
        ];
    }
}
