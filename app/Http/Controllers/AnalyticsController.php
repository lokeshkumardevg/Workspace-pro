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

        return Inertia::render('Analytics/Index', [
            'taskStats' => $taskStats,
            'topPerformers' => $topPerformers,
            'projectHealth' => $projectHealth,
            'attendanceTrend' => $attendanceTrend
        ]);
    }
}
