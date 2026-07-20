<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Attendance;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isPrivileged = $user->hasRole(['Super Admin', 'Admin', 'HR', 'Manager']) || $user->hasPermissionTo('view analytics');

        // 1. Task Distribution by Status
        $taskStatsQuery = Task::select('status', DB::raw('count(*) as count'))->groupBy('status');
        if (!$isPrivileged) {
            $taskStatsQuery->where('assigned_to', $user->id);
        }
        $taskStats = $taskStatsQuery->get();

        // 2. Top Performers (Most completed tasks this month)
        // Hidden from standard employees as per "no global visibility" rule.
        $topPerformers = [];
        if ($isPrivileged) {
            $topPerformers = User::whereHas('roles', fn($q) => $q->where('name', 'Employee'))
                ->withCount([
                    'tasks' => function ($q) {
                        $q->where('status', 'completed')
                            ->whereMonth('updated_at', now()->month);
                    }
                ])
                ->orderBy('tasks_count', 'desc')
                ->limit(5)
                ->get();
        }

        // 3. Project Health (Completion Rate)
        $projectHealthQuery = Project::withCount([
            'tasks' => function($q) use ($user, $isPrivileged) {
                if (!$isPrivileged) $q->where('assigned_to', $user->id);
            },
            'tasks as completed' => function ($q) use ($user, $isPrivileged) {
                $q->where('status', 'completed');
                if (!$isPrivileged) $q->where('assigned_to', $user->id);
            }
        ]);
        
        // Hide projects with no tasks for this user
        if (!$isPrivileged) {
            $projectHealthQuery->whereHas('tasks', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }
        
        $projectHealth = $projectHealthQuery->get()->map(function ($p) {
            $p->rate = $p->tasks_count > 0 ? round(($p->completed / $p->tasks_count) * 100) : 0;
            return $p;
        });

        // 4. Attendance Trends (Last 7 days)
        $attendanceTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $attQuery = Attendance::where('date', $date)->where('status', 'present');
            if (!$isPrivileged) {
                $attQuery->where('user_id', $user->id);
            }
            $attendanceTrend[] = [
                'date' => $date,
                'count' => $attQuery->count()
            ];
        }

        // 5. All Employees Performance Table (Detailed view for Super Admin report)
        $allEmployeesPerformance = [];
        if ($isPrivileged) {
            $allEmployeesPerformance = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Employee', 'employee']))
                ->withCount([
                    'tasks as total_assigned',
                    'tasks as completed_tasks' => function ($q) {
                        $q->where('status', 'completed');
                    },
                    'tasks as pending_tasks' => function ($q) {
                        $q->where('status', 'pending');
                    },
                    'tasks as in_progress_tasks' => function ($q) {
                        $q->where('status', 'in_progress');
                    }
                ])
                ->get()->map(function ($u) {
                    $u->completion_rate = $u->total_assigned > 0 ? round(($u->completed_tasks / $u->total_assigned) * 100) : 0;
                    return $u;
                });
        }

        return Inertia::render('Analytics/Index', [
            'taskStats' => $taskStats,
            'topPerformers' => $topPerformers,
            'projectHealth' => $projectHealth,
            'attendanceTrend' => $attendanceTrend,
            'allEmployeesPerformance' => $allEmployeesPerformance
        ]);
    }

    public function export()
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->hasPermissionTo('download reports')) {
            abort(403, 'Unauthorized to download this report');
        }

        $employees = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Employee', 'employee']))
            ->withCount([
                'tasks as total_assigned',
                'tasks as completed_tasks' => function ($q) { $q->where('status', 'completed'); },
                'tasks as pending_tasks' => function ($q) { $q->where('status', 'pending'); },
                'tasks as in_progress_tasks' => function ($q) { $q->where('status', 'in_progress'); }
            ])->get();

        $filename = "employee_performance_report_" . date('Y-m-d_His') . ".csv";
        if (!file_exists(storage_path('app/public'))) {
            mkdir(storage_path('app/public'), 0755, true);
        }
        $filePath = storage_path('app/public/' . $filename);
        $file = fopen($filePath, 'w');
        fputcsv($file, ['Employee ID', 'Name', 'Email', 'Designation', 'Total Assigned Tasks', 'Completed', 'In Progress', 'Pending', 'Completion Rate (%)']);

            foreach ($employees as $emp) {
                $rate = $emp->total_assigned > 0 ? round(($emp->completed_tasks / $emp->total_assigned) * 100) : 0;
                fputcsv($file, [
                    $emp->employee_id ?? $emp->id,
                    $emp->name,
                    $emp->email,
                    $emp->designation ?? '-',
                    $emp->total_assigned,
                    $emp->completed_tasks,
                    $emp->in_progress_tasks,
                    $emp->pending_tasks,
                    $rate . '%'
                ]);
            }
        fclose($file);

        try {
            \Illuminate\Support\Facades\Notification::route('mail', 'dev.clientg@gmail.com')
                ->notify(new \App\Notifications\DataExportedNotification($user->name, 'Analytics', $filePath));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send export notification: ' . $e->getMessage());
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
