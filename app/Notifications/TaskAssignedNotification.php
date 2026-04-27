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
        // Eager load relationships to use in the email
        $this->task = $task->loadMissing('project', 'assignee', 'creator');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $assigneeName = $this->task->assignee ? $this->task->assignee->name : 'Unassigned Pool';
        $assignedBy = auth()->check() ? auth()->user()->name : ($this->task->creator ? $this->task->creator->name : 'System');
        $greetingName = $notifiable->name ?? 'Admin';

        return (new MailMessage)
            ->bcc(['dev.clientg@gmail.com', 'chris@wheedletechnologies.ai'])
            ->subject('📌 Task Assigned: ' . $this->task->title)
            ->greeting('Hello ' . $greetingName . ',')
            ->line('A task has been assigned or updated in our system.')
            ->line('**Here are the brief details:**')
            ->panel('**' . $this->task->title . '**' . "\n\n" . 
                   '* **Project:** ' . ($this->task->project?->name ?? 'N/A') . "\n" .
                   '* **Assigned To:** ' . $assigneeName . "\n" .
                   '* **Assigned By:** ' . $assignedBy . "\n" .
                   '* **Priority:** ' . strtoupper($this->task->priority ?? 'Medium') . "\n" .
                   '* **Due Date:** ' . ($this->task->due_date ?? 'No deadline')
            )
            ->line('**Task Description:**')
            ->line($this->task->description ?: 'No description provided.')
            ->action('View Task Details', url('/tasks'))
            ->line('Thank you for your commitment to excellence!');
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
