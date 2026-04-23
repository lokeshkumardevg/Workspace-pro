<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->getAllPermissions()->pluck('name')->contains('manage payroll') ||
            $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR'])->count() > 0;

        $query = Payroll::with('user')->orderBy('year', 'desc')->orderBy('month', 'desc');

        if (!$isPrivileged) {
            $query->where('user_id', $user->id);
        }

        $payrolls = $query->paginate(12)->withQueryString();
        $employees = [];
        if ($isPrivileged) {
            $employees = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Employee', 'employee']))->get();
        }

        $settings = SystemSetting::all()->pluck('value', 'key');

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'employees' => $employees,
            'canManage' => $isPrivileged,
            'settings' => $settings,
            'stats' => [
                'total' => Payroll::count(),
                'paid' => Payroll::where('status', 'paid')->count(),
                'pending' => Payroll::where('status', 'pending')->count(),
                'total_amount' => Payroll::where('status', 'paid')->sum('net_salary'),
            ],
        ]);
    }

    /**
     * Auto-generate payroll based on attendance
     */
    public function autoGenerate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'bonuses' => 'nullable|numeric|min:0',
            'extra_deductions' => 'nullable|numeric|min:0',
        ]);

        $employee = User::findOrFail($request->user_id);
        $month = $request->month;
        $year = $request->year;

        $baseSalary = $employee->base_salary ?? 0;

        // Calculate working days in the month (excluding Sundays only)
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $extendedStartDate = $startDate->copy()->subDays(5);
        $extendedEndDate = $endDate->copy()->addDays(5);

        $attendances = Attendance::where('user_id', $employee->id)
            ->whereBetween('date', [$extendedStartDate->toDateString(), $extendedEndDate->toDateString()])
            ->get()
            ->keyBy('date');

        $monthlyLeaves = \App\Models\LeaveRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('from_date', [$startDate, $endDate])
                    ->orWhereBetween('to_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('from_date', '<', $startDate)
                            ->where('to_date', '>', $endDate);
                    });
            })->get();

        $absentDays = 0;
        $unpaidLeaves = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->startOfDay()->lte(now()->startOfDay())) {
                $dateStr = $date->toDateString();

                $isUnpaidLeaveDate = false;
                $isPaidLeaveDate = false;
                foreach ($monthlyLeaves as $l) {
                    if ($date->between($l->from_date, $l->to_date)) {
                        if (!$l->is_paid) {
                            $isUnpaidLeaveDate = true;
                        } else {
                            $isPaidLeaveDate = true;
                        }
                    }
                }

                if ($isUnpaidLeaveDate) {
                    $unpaidLeaves++;
                } else {
                    $attendance = $attendances[$dateStr] ?? null;
                    if ($attendance && $attendance->status === 'absent') {
                        $absentDays++;
                    } elseif (!$attendance && !$isPaidLeaveDate) {
                        if ($date->isWeekend()) {
                            $friday = $date->copy()->previous(Carbon::FRIDAY);
                            $monday = $date->copy()->next(Carbon::MONDAY);
                            $frAt = $attendances[$friday->toDateString()] ?? null;
                            $moAt = $attendances[$monday->toDateString()] ?? null;

                            $isFrAbsent = (!$frAt || $frAt->status === 'absent');
                            $isMoAbsent = (!$moAt || $moAt->status === 'absent');

                            if ($isFrAbsent && $isMoAbsent) {
                                $absentDays++;
                            }
                        } else {
                            $absentDays++; // Missing weekday
                        }
                    } elseif ($attendance && in_array($attendance->status, ['half_day', 'half-day'])) {
                        $absentDays += 0.5;
                    }
                }
            }
        }

        // 🔹 30-Day Fixed Formula 🔹
        $payableDays = max(0, 30 - $absentDays - $unpaidLeaves);
        $perDaySalary = $baseSalary > 0 ? $baseSalary / 30 : 0;
        $basicNet = $perDaySalary * $payableDays;

        // Deductions & Tax (Keeping legacy struct if needed)
        $pfContribution = min(round($baseSalary * 0.12, 2), 1800);
        $professionalTax = $baseSalary > 15000 ? 200 : ($baseSalary > 10000 ? 150 : 0);

        $bonuses = $request->bonuses ?? 0;
        $extraDeductions = $request->extra_deductions ?? 0;
        $totalDeductions = $pfContribution + $professionalTax + $extraDeductions;

        // Final Payable Calculation
        $netSalary = max(0, round($basicNet + $bonuses - $totalDeductions, 2));

        Payroll::updateOrCreate(
            ['user_id' => $employee->id, 'month' => $month, 'year' => $year],
            [
                'base_salary' => $baseSalary,
                'bonuses' => $bonuses,
                'deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'status' => 'pending',
                'working_days' => 30, // Month Fix = 30 Days
                'present_days' => $payableDays, // Saved as Payable Days for report
                'lop_days' => $absentDays,
                'pf_contribution' => $pfContribution,
                'professional_tax' => $professionalTax,
                'lop_deduction' => $unpaidLeaves, // Saved as Unpaid Leave count
                'extra_deductions' => $extraDeductions,
            ]
        );

        return redirect()->back()->with('success', "✅ Payroll for {$employee->name} ({$month}/{$year}) auto-generated effectively based on the 30-day fixed formula.");
    }

    public function sendSlip(Request $request, Payroll $payroll)
    {
        $payroll->load('user');

        try {
            $payroll->user->notify(new \App\Notifications\SalarySlipNotification($payroll));
            return redirect()->back()->with('success', '📧 Salary slip successfully emailed to ' . $payroll->user->name);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Slip Email Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', '❌ Failed to send salary slip. Please check SMTP settings.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'base_salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ]);

        $netSalary = $request->base_salary + ($request->bonuses ?? 0) - ($request->deductions ?? 0);

        Payroll::updateOrCreate(
            ['user_id' => $request->user_id, 'month' => $request->month, 'year' => $request->year],
            [
                'base_salary' => $request->base_salary,
                'deductions' => $request->deductions ?? 0,
                'bonuses' => $request->bonuses ?? 0,
                'net_salary' => $netSalary,
                'status' => 'pending',
            ]
        );

        return redirect()->back()->with('success', '✅ Payroll record generated successfully.');
    }

    public function update(Request $request, Payroll $payroll)
    {
        $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ]);

        $netSalary = $request->base_salary + ($request->bonuses ?? 0) - ($request->deductions ?? 0);

        $payroll->update([
            'base_salary' => $request->base_salary,
            'deductions' => $request->deductions ?? 0,
            'bonuses' => $request->bonuses ?? 0,
            'net_salary' => $netSalary,
        ]);

        return redirect()->back()->with('success', '✅ Payroll record updated successfully.');
    }

    public function updateStatus(Request $request, Payroll $payroll)
    {
        $request->validate(['status' => 'required|in:pending,paid']);
        $payroll->update(['status' => $request->status]);
        return redirect()->back()->with('success', '💰 Payroll status updated successfully.');
    }

    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return redirect()->back()->with('success', '🗑️ Payroll record removed.');
    }
}
