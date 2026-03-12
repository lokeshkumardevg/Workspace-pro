<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Task Overdue: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->error()
            ->line('One of your assigned tasks has passed its deadline and is still not completed.')
            ->line('**Task:** ' . $this->task->title)
            ->line('**Project:** ' . ($this->task->project->name ?? 'N/A'))
            ->line('**Original Deadline:** ' . ($this->task->due_date ?? 'N/A'))
            ->action('Complete Task Now', url('/tasks'))
            ->line('Please prioritize this task to avoid further delays.');
    }

    public function toArray($notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => '🚨 Task Overdue!',
            'message' => 'The task "' . $this->task->title . '" is past its deadline.',
            'type' => 'task_overdue',
            'action_url' => '/tasks'
        ];
    }
}
