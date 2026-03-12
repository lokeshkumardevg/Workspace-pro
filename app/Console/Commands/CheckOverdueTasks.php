<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Notify users about overdue tasks';

    public function handle()
    {
        $overdueTasks = Task::with('assignee')
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', Carbon::today())
            ->get();

        $count = 0;
        foreach ($overdueTasks as $task) {
            if ($task->assignee) {
                $task->assignee->notify(new TaskOverdueNotification($task));
                $count++;
            }
        }

        $this->info("Successfully sent {$count} overdue notifications.");
    }
}
