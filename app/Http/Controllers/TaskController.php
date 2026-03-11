<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'Manager'])->count() > 0;

        $tasksQuery = Task::with('project', 'assignee', 'creator', 'comments.user');

        // Search
        if ($request->search) {
            $tasksQuery->where('title', 'like', "%{$request->search}%");
        }

        // Date Filters
        $this->applyFilters($tasksQuery, $request);

        // Security: Employees only see their own tasks
        if (!$isPrivileged) {
            $tasksQuery->where('assigned_to', $user->id);
        }

        // Performance Calculation Logic
        $performanceData = $this->calculatePerformance($user, $request, $isPrivileged);

        $tasks = $tasksQuery->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $projects = Project::all();
        $users = User::role(['Employee', 'Admin', 'Super Admin', 'Manager', 'HR'])->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'performance' => $performanceData['score'],
            'performanceLabel' => $performanceData['label'],
            'filters' => $request->only(['search', 'filter_type', 'start_date', 'end_date'])
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'Manager'])->count() > 0;

        $tasksQuery = Task::with('project', 'assignee', 'creator');
        $this->applyFilters($tasksQuery, $request);

        if (!$isPrivileged) {
            $tasksQuery->where('assigned_to', $user->id);
        }

        $tasks = $tasksQuery->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=tasks_report_" . date('Y-md') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Title', 'Project', 'Assigned To', 'Status', 'Due Date', 'Created At'];

        $callback = function () use ($tasks, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($tasks as $task) {
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->project ? $task->project->name : 'N/A',
                    $task->assignee ? $task->assignee->name : 'Unassigned',
                    strtoupper($task->status),
                    $task->due_date ?? 'N/A',
                    $task->created_at->format('Y-m-d')
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function applyFilters($query, $request)
    {
        $filter = $request->filter_type ?? 'all';
        $now = Carbon::now();

        if ($filter === 'weekly') {
            $query->whereBetween('created_at', [$now->startOfWeek()->toDateTimeString(), $now->endOfWeek()->toDateTimeString()]);
        } elseif ($filter === 'monthly') {
            $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        } elseif ($filter === 'custom' && $request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }
    }

    private function calculatePerformance($user, $request, $isPrivileged)
    {
        // For performance, we usually look at the assigned tasks of the current user 
        // OR if admin looks at a specific period

        $perfQuery = Task::query();
        $this->applyFilters($perfQuery, $request);

        if (!$isPrivileged) {
            $perfQuery->where('assigned_to', $user->id);
            $label = "My Performance";
        } else {
            $label = "Team Progress";
        }

        $total = (clone $perfQuery)->count();
        $completed = (clone $perfQuery)->where('status', 'completed')->count();

        $score = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'score' => $score,
            'label' => $label
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'project_id' => $request->project_id,
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        // Notify Assignee
        $assignee = User::find($request->assigned_to);
        if ($assignee) {
            $assignee->notify(new \App\Notifications\TaskAssignedNotification($task));
        }

        return redirect()->back()->with('success', '✅ Task assigned and performance tracking updated.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $task->update(['status' => $request->status]);

        return redirect()->back()->with('success', '✅ Task status updated. Performance metrics recalculated.');
    }

    public function reassign(Request $request, Task $task)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $task->update(['assigned_to' => $request->assigned_to]);

        // Notify New Assignee
        $newAssignee = User::find($request->assigned_to);
        if ($newAssignee) {
            $newAssignee->notify(new \App\Notifications\TaskAssignedNotification($task));
        }

        return redirect()->back()->with('success', '✅ Task successfully reassigned to ' . ($newAssignee->name ?? 'new member') . '.');
    }
}
