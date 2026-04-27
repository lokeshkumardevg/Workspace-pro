<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    // Queueable REMOVED — fires synchronously, no queue worker needed

    private $task;

    public function __construct(Task $task)
    {
        // Eager load project to avoid null errors in toMail
        $this->task = $task->loadMissing('project');
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
            ->line('**Project:** ' . ($this->task->project?->name ?? 'N/A'))
            ->line('**Due Date:** ' . ($this->task->due_date ?? 'No deadline'))
            ->action('View Task', url('/tasks'))
            ->line('Please complete the task on time. Good luck!');
    }

    public function toArray($notifiable): array
    {
        return [
            'task_id'    => $this->task->id,
            'title'      => 'New Task: ' . $this->task->title,
            'message'    => 'You have been assigned to ' . $this->task->title,
            'type'       => 'task_assigned',
            'action_url' => '/tasks',
        ];
    }
}
