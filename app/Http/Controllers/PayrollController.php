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
        $isPrivileged = $user->hasRole(['Super Admin', 'Admin', 'HR', 'super admin', 'admin', 'hr', 'Superadmin', 'superadmin']) ||
            $user->hasPermissionTo('view payroll');

        $query = Payroll::with('user')->orderBy('year', 'desc')->orderBy('month', 'desc');

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }
        if ($request->month) {
            $query->where('month', $request->month);
        }
        if ($request->year) {
            $query->where('year', $request->year);
        }

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
            'filters' => $request->only('search', 'month', 'year'),
            'stats' => [
                'total' => Payroll::when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->count(),
                'paid' => Payroll::when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->where('status', 'paid')->count(),
                'pending' => Payroll::when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->where('status', 'pending')->count(),
                'total_amount' => Payroll::when(!$isPrivileged, fn($q) => $q->where('user_id', $user->id))->where('status', 'paid')->sum('net_salary'),
            ],
        ]);
    }

    public function saveSetup(Request $request)
    {
        $request->validate([
            'salaries' => 'required|array',
            'salaries.*.id' => 'required|exists:users,id',
            'salaries.*.base_salary' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $isPrivileged = $user->hasRole(['Super Admin', 'Admin', 'HR', 'super admin', 'admin', 'hr', 'Superadmin', 'superadmin']) ||
            $user->hasPermissionTo('create payroll');

        if (!$isPrivileged)
            abort(403);

        foreach ($request->salaries as $data) {
            User::where('id', $data['id'])->update(['base_salary' => $data['base_salary']]);
        }

        return redirect()->back()->with('success', '✅ Salaries updated successfully.');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $employees = [];
        if ($request->user_id === 'all') {
            $employees = User::whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Super Admin']))->get();
        } else {
            $employees = User::where('id', $request->user_id)->get();
        }

        $previewData = [];
        foreach ($employees as $employee) {
            $previewData[] = $this->calculatePayrollForUser($employee, $request->month, $request->year, 0, 0);
        }

        return response()->json(['preview' => $previewData]);
    }

    public function autoGenerate(Request $request)
    {
        $request->validate([
            'payrolls' => 'required|array',
            'payrolls.*.user_id' => 'required|exists:users,id',
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $generatedCount = 0;

        foreach ($request->payrolls as $pData) {
            $monthDays = \Carbon\Carbon::createFromDate($request->year, $request->month, 1)->daysInMonth;
            $perDaySalary = $pData['base_salary'] > 0 ? $pData['base_salary'] / $monthDays : 0;
            $payableDays = $pData['present_days'] ?? 0;
            $basicNet = $perDaySalary * $payableDays;

            // Re-calculate the net based on UI adjustments
            $netSalary = max(0, round($basicNet + ($pData['bonuses'] ?? 0) - ($pData['deductions'] ?? 0), 2));

            Payroll::updateOrCreate(
                ['user_id' => $pData['user_id'], 'month' => $request->month, 'year' => $request->year],
                [
                    'base_salary' => $pData['base_salary'],
                    'bonuses' => $pData['bonuses'],
                    'deductions' => $pData['deductions'],
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'working_days' => $pData['working_days'] ?? 30,
                    'present_days' => $pData['present_days'] ?? 0,
                    'lop_days' => $pData['lop_days'] ?? 0,
                    'pf_contribution' => $pData['pf_contribution'] ?? 0,
                    'professional_tax' => $pData['professional_tax'] ?? 0,
                    'lop_deduction' => $pData['lop_deduction'] ?? 0,
                    'extra_deductions' => $pData['extra_deductions'] ?? 0,
                ]
            );
            $generatedCount++;
        }

        return redirect()->back()->with('success', "✅ Payroll auto-generated effectively for {$generatedCount} employee(s).");
    }

    private function calculatePayrollForUser($employee, $month, $year, $bonuses, $extraDeductions)
    {
        $baseSalary = $employee->base_salary ?? 0;

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

        $monthDays = $startDate->daysInMonth;
        $workingDays = 0;
        $weekends = 0;
        $holidaysCount = 0;
        $present = 0;
        $absent = 0;
        $leaveDays = 0;
        $halfDays = 0;
        $sandwichAbsent = 0;

        $monthlyHolidays = \App\Models\Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();

            $isWeekend = false;
            if ($date->isSunday()) {
                $isWeekend = true;
            } elseif ($date->isSaturday()) {
                $satNum = ceil($date->day / 7);
                if ($satNum == 2 || $satNum == 4) {
                    $isWeekend = true;
                }
            }
            $isHolidayDB = in_array($dateStr, $monthlyHolidays);

            $isLeaveDate = false;
            foreach ($monthlyLeaves as $lReq) {
                if ($date->between($lReq->from_date, $lReq->to_date)) {
                    $isLeaveDate = true;
                    break;
                }
            }

            if ($isHolidayDB) {
                $holidaysCount++;
            } elseif ($isWeekend) {
                $weekends++;
                // Sandwich Rule Check
                if (!$isLeaveDate) {
                    $friday = $date->copy()->previous(Carbon::FRIDAY);
                    $monday = $date->copy()->next(Carbon::MONDAY);
                    $frAttendance = $attendances[$friday->toDateString()] ?? null;
                    $moAttendance = $attendances[$monday->toDateString()] ?? null;

                    $fridayLeave = false;
                    $mondayLeave = false;
                    foreach ($monthlyLeaves as $l) {
                        if ($friday->between($l->from_date, $l->to_date))
                            $fridayLeave = true;
                        if ($monday->between($l->from_date, $l->to_date))
                            $mondayLeave = true;
                    }

                    $isFridayAbsent = (!$frAttendance || $frAttendance->status === 'absent') && !$fridayLeave;
                    $isMondayAbsent = (!$moAttendance || $moAttendance->status === 'absent') && !$mondayLeave;

                    if ($isFridayAbsent && $isMondayAbsent) {
                        $absent++; // Sandwich rule absent
                        $sandwichAbsent++;
                    }
                }
            } else {
                $workingDays++;

                $attendance = $attendances[$dateStr] ?? null;
                if ($attendance) {
                    if (in_array($attendance->status, ['present', 'late'])) {
                        $present++;
                    } elseif (in_array($attendance->status, ['half_day', 'half-day'])) {
                        $present += 0.5;
                        $absent += 0.5; // Half Day logic
                        $halfDays++;
                    } elseif ($attendance->status === 'absent') {
                        if ($isLeaveDate) {
                            $leaveDays++;
                        } else {
                            $absent++;
                        }
                    }
                } else {
                    if ($date->startOfDay()->lte(now()->startOfDay())) {
                        if ($isLeaveDate) {
                            $leaveDays++;
                        } else {
                            $absent++;
                        }
                    }
                }
            }
        }

        $totalPaidDays = $present + $weekends + $holidaysCount + $leaveDays - $sandwichAbsent;
        $payableDays = max(0, $totalPaidDays);
        $perDaySalary = $baseSalary > 0 ? $baseSalary / $monthDays : 0;
        $basicNet = $perDaySalary * $payableDays;

        $pfContribution = 0; // Disabled automatic PF deduction
        $professionalTax = 0; // Disabled automatic tax deduction

        $totalDeductions = $extraDeductions;
        $netSalary = max(0, round($basicNet + $bonuses - $totalDeductions, 2));

        return [
            'user_id' => $employee->id,
            'user' => $employee,
            'base_salary' => $baseSalary,
            'bonuses' => $bonuses,
            'deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'working_days' => $monthDays,
            'present_days' => $payableDays,
            'lop_days' => $absent,
            'pf_contribution' => $pfContribution,
            'professional_tax' => $professionalTax,
            'lop_deduction' => $absent,
            'extra_deductions' => $extraDeductions,
        ];
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
