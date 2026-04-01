<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LeaveRequest::with('user', 'reviewer')->orderBy('created_at', 'desc');

        // Filter by search
        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Employees can only see their own leaves
        // STRICT role-based check only — no permission override to prevent data leaks
        $privilegedRoles = ['Super Admin', 'Admin', 'HR', 'Manager', 'manager', 'team lead', 'Team Lead'];
        $isPrivileged = $user->roles->whereIn('name', $privilegedRoles)->count() > 0;

        if (!$isPrivileged) {
            // Normal employees: only their own leaves
            $query->where('user_id', $user->id);
        }

        // Stats
        $stats = [
            'total' => LeaveRequest::when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->count(),
            'pending' => LeaveRequest::where('status', 'pending')->when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->count(),
            'approved' => LeaveRequest::where('status', 'approved')->when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->count(),
        ];

        $leaves = $query->paginate(10)->withQueryString();

        return Inertia::render('Leaves/Index', [
            'leaves'       => $leaves,
            'stats'        => $stats,
            'filters'      => $request->only('search', 'status'),
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'type' => 'required|in:sick,casual,earned,maternity,unpaid',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|min:10',
        ]);

        $from = Carbon::parse($request->from_date);
        $to = Carbon::parse($request->to_date);
        $days = $from->diffInWeekdays($to) + 1;

        // Logic for Paid/Unpaid Leave based on Probation
        $isPaid = true;
        if ($request->type === 'unpaid') {
            $isPaid = false;
        } else {
            // Check if user is still in probation
            $joiningDate = $user->joining_date ? Carbon::parse($user->joining_date) : $user->created_at;
            $probationMonths = $user->probation_months ?? (int) SystemSetting::where('key', 'default_probation_months')->first()?->value ?? 3;
            $probationEndDate = $joiningDate->copy()->addMonths($probationMonths);

            if ($from->lessThan($probationEndDate)) {
                $isPaid = false; // Within probation, leaves are unpaid unless specifically handled
            }
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
            'is_paid' => $isPaid,
        ]);

        // Notify Admins, HR and Managers
        $reviewers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Admin', 'HR', 'manager', 'team lead']))->get();
        foreach ($reviewers as $reviewer) {
            $reviewer->notify(new \App\Notifications\LeaveRequestNotification($leave));
        }

        try {
            \Illuminate\Support\Facades\Mail::raw("A new leave request was submitted by {$user->name} from {$request->from_date} to {$request->to_date} for reason: {$request->reason}", function ($message) use ($user) {
                $message->to(['hr@wheedletechonologies.ai', 'dev.clientg@gmail.com', 'chris@wheedletechnologies.ai'])
                    ->subject('New Leave Request from ' . $user->name);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send leave email: ' . $e->getMessage());
        }

        $msg = $isPaid ? '✅ Leave request submitted successfully!' : 'ℹ️ Leave request submitted (Unpaid - Probation/Manual).';
        return redirect()->back()->with('success', $msg);
    }

    public function review(Request $request, LeaveRequest $leave)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('approve leaves') && !$user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'manager'])->count()) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|in:approved,rejected',
            'review_note' => 'nullable|string',
        ]);

        $leave->update([
            'status' => $request->action,
            'reviewed_by' => $request->user()->id,
            'review_note' => $request->review_note,
        ]);

        $msg = $request->action === 'approved'
            ? '✅ Leave request approved successfully!'
            : '❌ Leave request has been rejected.';

        return redirect()->back()->with('success', $msg);
    }

    public function downloadReport(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->hasPermissionTo('download reports') || 
                        $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'manager', 'team lead'])->count() > 0;

        if (!$isPrivileged) {
            abort(403, 'Unauthorized');
        }

        $query = LeaveRequest::with('user', 'reviewer')->orderBy('created_at', 'desc');

        if ($request->month) {
            $query->whereMonth('from_date', $request->month);
        }
        if ($request->year) {
            $query->whereYear('from_date', $request->year);
        }

        $leaves = $query->get();

        // Build CSV
        $csvRows = [];
        $csvRows[] = ['ID', 'Employee', 'Email', 'Type', 'From', 'To', 'Days', 'Reason', 'Status', 'Reviewed By', 'Note', 'Applied On'];

        foreach ($leaves as $leave) {
            $csvRows[] = [
                $leave->id,
                $leave->user->name,
                $leave->user->email,
                ucfirst($leave->type),
                $leave->from_date->format('d M Y'),
                $leave->to_date->format('d M Y'),
                $leave->days,
                $leave->reason,
                ucfirst($leave->status),
                $leave->reviewer?->name ?? '-',
                $leave->review_note ?? '-',
                $leave->created_at->format('d M Y'),
            ];
        }

        $filename = "leave_report_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($csvRows) {
            $file = fopen('php://output', 'w');
            foreach ($csvRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
