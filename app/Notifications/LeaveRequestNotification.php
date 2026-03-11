<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification
{
    use Queueable;

    private $leaveRequest;

    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Leave Request from ' . $this->leaveRequest->user->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new leave request has been submitted and is awaiting your review.')
            ->line('**Employee:** ' . $this->leaveRequest->user->name)
            ->line('**Leave Type:** ' . ucfirst($this->leaveRequest->type))
            ->line('**Duration:** ' . $this->leaveRequest->from_date->format('d M') . ' to ' . $this->leaveRequest->to_date->format('d M') . ' (' . $this->leaveRequest->days . ' days)')
            ->line('**Reason:** ' . $this->leaveRequest->reason)
            ->action('Review Request', url('/leaves'))
            ->line('Please review this request at your earliest convenience.');
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_request_id' => $this->leaveRequest->id,
            'user_name' => $this->leaveRequest->user->name,
            'message' => 'New leave request from ' . $this->leaveRequest->user->name,
            'type' => 'leave_request'
        ];
    }
}
