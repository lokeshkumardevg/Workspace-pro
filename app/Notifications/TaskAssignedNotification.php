<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
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
            ->subject('New Task Assigned: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new task has been assigned to you.')
            ->line('**Task:** ' . $this->task->title)
            ->line('**Project:** ' . ($this->task->project->name ?? 'N/A'))
            ->line('**Due Date:** ' . ($this->task->due_date ?? 'No deadline'))
            ->action('View Task', url('/tasks'))
            ->line('Please complete the task on time. Good luck!');
    }

    public function toArray($notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'New task assigned: ' . $this->task->title,
            'type' => 'task_assigned'
        ];
    }
}
