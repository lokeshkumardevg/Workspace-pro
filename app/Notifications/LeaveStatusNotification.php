<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    use Queueable;

    private $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $status = ucfirst($this->leave->status);
        $color = $this->leave->status === 'approved' ? 'success' : 'error';
        $message = $this->leave->status === 'approved' 
            ? 'Your leave request has been approved.' 
            : 'Your leave request has been rejected.';

        return (new MailMessage)
            ->subject('Leave Request ' . $status)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($message)
            ->line('**From Date:** ' . \Carbon\Carbon::parse($this->leave->from_date)->format('d M Y'))
            ->line('**To Date:** ' . \Carbon\Carbon::parse($this->leave->to_date)->format('d M Y'))
            ->line('**Days:** ' . $this->leave->days)
            ->line('**Review Note:** ' . ($this->leave->review_note ?? 'No notes provided.'))
            ->action('View Dashboard', url('/'))
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'title' => 'Leave ' . ucfirst($this->leave->status),
            'message' => 'Your leave from ' . \Carbon\Carbon::parse($this->leave->from_date)->format('d M') . ' to ' . \Carbon\Carbon::parse($this->leave->to_date)->format('d M') . ' was ' . $this->leave->status,
            'type' => 'leave_status',
            'action_url' => '/'
        ];
    }
}
