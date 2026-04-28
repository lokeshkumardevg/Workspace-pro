<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
{
    private $task;
    private $completedBy;

    public function __construct(Task $task, $completedBy)
    {
        $this->task = $task->loadMissing('project', 'assignee');
        $this->completedBy = $completedBy;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->bcc(['dev.clientg@gmail.com', 'chris@wheedletechnologies.ai'])
            ->subject('✅ Task Completed: ' . $this->task->title)
            ->greeting('Hello Admin,')
            ->line('A task has been marked as **Completed**.')
            ->line('**Completion Details:**')
            ->line('- **Task:** ' . $this->task->title)
            ->line('- **Completed By:** ' . $this->completedBy)
            ->line('- **Project:** ' . ($this->task->project?->name ?? 'N/A'))
            ->line('- **Time Spent:** ' . ($this->task->time_spent ?? 'Not recorded'))
            ->action('View All Tasks', url('/tasks'))
            ->line('The task and its performance metrics have been updated in the system.');
    }

    public function toArray($notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => 'Task Completed: ' . $this->task->title,
            'message' => $this->completedBy . ' completed the task: ' . $this->task->title,
            'type' => 'task_completed',
            'action_url' => '/tasks',
        ];
    }
}
