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
        if (!$user->hasRole(['Super Admin', 'Admin', 'HR', 'Manager'])) {
            abort(403);
        }

        // 1. Task Distribution by Status
        $taskStats = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // 2. Top Performers (Most completed tasks this month)
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

        // 3. Project Health (Completion Rate)
        $projectHealth = Project::withCount([
            'tasks',
            'tasks as completed' => function ($q) {
                $q->where('status', 'completed');
            }
        ])->get()->map(function ($p) {
            $p->rate = $p->tasks_count > 0 ? round(($p->completed / $p->tasks_count) * 100) : 0;
            return $p;
        });

        // 4. Attendance Trends (Last 7 days)
        $attendanceTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $attendanceTrend[] = [
                'date' => $date,
                'count' => Attendance::where('date', $date)->where('status', 'present')->count()
            ];
        }

        // 5. All Employees Performance Table (Detailed view for Super Admin report)
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
        if (!$user->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can download this report');
        }

        $employees = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Employee', 'employee']))
            ->withCount([
                'tasks as total_assigned',
                'tasks as completed_tasks' => function ($q) { $q->where('status', 'completed'); },
                'tasks as pending_tasks' => function ($q) { $q->where('status', 'pending'); },
                'tasks as in_progress_tasks' => function ($q) { $q->where('status', 'in_progress'); }
            ])->get();

        $filename = "employee_performance_report_" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
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
        };

        return response()->stream($callback, 200, $headers);
    }
}
