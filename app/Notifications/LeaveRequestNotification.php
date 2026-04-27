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
            ->subject('🌴 New Leave Request: ' . $this->leaveRequest->user->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An employee has submitted a new leave request for your review.')
            ->panel(
                "**Employee:** " . $this->leaveRequest->user->name . "\n" .
                "**Type:** " . ucfirst($this->leaveRequest->type) . "\n" .
                "**Period:** " . \Carbon\Carbon::parse($this->leaveRequest->from_date)->format('d M') . " - " . \Carbon\Carbon::parse($this->leaveRequest->to_date)->format('d M') . " ({$this->leaveRequest->days} days)"
            )
            ->line("**Reason for Leave:**")
            ->line($this->leaveRequest->reason ?: 'No reason provided.')
            ->action('Review & Decide', url('/leaves'))
            ->line('Please take action on this request to ensure team planning remains smooth.');
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_request_id' => $this->leaveRequest->id,
            'title' => 'Leave Request: ' . $this->leaveRequest->user->name,
            'user_name' => $this->leaveRequest->user->name,
            'message' => $this->leaveRequest->user->name . ' is requesting ' . $this->leaveRequest->days . ' days off.',
            'type' => 'leave_request',
            'action_url' => '/leaves'
        ];
    }
}
