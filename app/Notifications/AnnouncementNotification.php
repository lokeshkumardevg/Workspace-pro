<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    private $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📢 New Announcement: ' . $this->announcement->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new official announcement has been posted on the dashboard.')
            ->panel(
                "**" . $this->announcement->title . "**"
            )
            ->line($this->announcement->content)
            ->action('Open Dashboard', url('/'))
            ->line('Stay updated and have a great day!');
    }
}
