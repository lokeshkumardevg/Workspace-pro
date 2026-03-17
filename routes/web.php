<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $isPrivileged = $user->hasRole(['Super Admin', 'Admin', 'HR', 'Manager']);

    $stats = [];

    // Projects
    if ($user->can('manage projects') || $isPrivileged) {
        $stats['projects'] = \App\Models\Project::count();
    } else {
        $stats['projects'] = \App\Models\Project::whereHas('tasks', function ($q) use ($user) {
            $q->where('assigned_to', $user->id);
        })->distinct()->count();
    }

    // Tasks
    if ($user->can('view tasks') || $isPrivileged) {
        $stats['tasks'] = \App\Models\Task::count();
    } else {
        $stats['tasks'] = \App\Models\Task::where('assigned_to', $user->id)->count();
    }

    // Leads
    if ($user->can('view leads') || $isPrivileged) {
        $stats['leads'] = \App\Models\Lead::count();
    }

    // Attendance
    if ($isPrivileged) {
        $stats['attendance_today'] = \App\Models\Attendance::where('date', \Carbon\Carbon::today())->count();
    } else {
        $stats['attendance_today'] = \App\Models\Attendance::where('user_id', $user->id)->count(); // Career total or month? Let's say month
    }

    // Leaves
    if ($user->can('approve leaves') || $isPrivileged) {
        $stats['pending_leaves'] = \App\Models\LeaveRequest::where('status', 'pending')->count();
    } else {
        $stats['pending_leaves'] = \App\Models\LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count();
    }

    // Advanced Metrics: Project Progress
    $stats['project_progress'] = \App\Models\Project::withCount([
        'tasks',
        'tasks as completed_tasks_count' => function ($query) {
            $query->where('status', 'completed');
        }
    ])->get()->map(function ($project) {
        $project->percentage = $project->tasks_count > 0
            ? round(($project->completed_tasks_count / $project->tasks_count) * 100)
            : 0;
        return $project;
    });

    // Recent Activity Feed
    $stats['recent_activity'] = \App\Models\TaskComment::with('user', 'task')
        ->latest()
        ->limit(6)
        ->get();

    return Inertia::render('Dashboard', [
        'stats' => $stats
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects
    Route::get('/projects', [\App\Http\Controllers\ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');

    // Tasks
    Route::get('/tasks', [\App\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [\App\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
    Route::get('/tasks/export', [\App\Http\Controllers\TaskController::class, 'export'])->name('tasks.export');
    Route::put('/tasks/{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::put('/tasks/{task}/reassign', [\App\Http\Controllers\TaskController::class, 'reassign'])->name('tasks.reassign');
    Route::post('/tasks/{task}/comments', [\App\Http\Controllers\TaskCommentController::class, 'store'])->name('tasks.comments.store');

    // Attendance
    Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [\App\Http\Controllers\AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [\App\Http\Controllers\AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::get('/attendance/export', [\App\Http\Controllers\AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('/attendance/report', [\App\Http\Controllers\AttendanceController::class, 'report'])->name('attendance.report');

    // Leads (CRM)
    Route::get('/leads', [\App\Http\Controllers\LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads', [\App\Http\Controllers\LeadController::class, 'store'])->name('leads.store');
    Route::put('/leads/{lead}/status', [\App\Http\Controllers\LeadController::class, 'updateStatus'])->name('leads.status');

    // Leave Management
    Route::get('/leaves', [\App\Http\Controllers\LeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [\App\Http\Controllers\LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/review', [\App\Http\Controllers\LeaveController::class, 'review'])->name('leaves.review');
    Route::get('/leaves/download', [\App\Http\Controllers\LeaveController::class, 'downloadReport'])->name('leaves.download');

    // Users and Roles
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/sync-roles', [\App\Http\Controllers\UserController::class, 'syncRoles'])->name('users.sync-roles');

    Route::resource('roles', \App\Http\Controllers\RoleController::class)->only(['index', 'store', 'update', 'destroy']);

    // Notifications
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

    // Announcements
    Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // System Settings & Branding
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    // Holidays
    Route::resource('holidays', \App\Http\Controllers\HolidayController::class);

    // Payroll
    Route::get('/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll', [\App\Http\Controllers\PayrollController::class, 'store'])->name('payroll.store');
    Route::post('/payroll/auto-generate', [\App\Http\Controllers\PayrollController::class, 'autoGenerate'])->name('payroll.auto-generate');
    Route::put('/payroll/{payroll}/status', [\App\Http\Controllers\PayrollController::class, 'updateStatus'])->name('payroll.status');
    Route::delete('/payroll/{payroll}', [\App\Http\Controllers\PayrollController::class, 'destroy'])->name('payroll.destroy');
});

require __DIR__ . '/auth.php';
