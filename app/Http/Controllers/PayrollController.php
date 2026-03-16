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
            'payrolls'   => $payrolls,
            'employees'  => $employees,
            'canManage'  => $isPrivileged,
            'settings'   => $settings,
            'stats'      => [
                'total'        => Payroll::count(),
                'paid'         => Payroll::where('status', 'paid')->count(),
                'pending'      => Payroll::where('status', 'pending')->count(),
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
            'month'   => 'required|integer|min:1|max:12',
            'year'    => 'required|integer|min:2020|max:2030',
            'bonuses'     => 'nullable|numeric|min:0',
            'extra_deductions' => 'nullable|numeric|min:0',
        ]);

        $employee = User::findOrFail($request->user_id);
        $month = $request->month;
        $year  = $request->year;

        $baseSalary = $employee->base_salary ?? 0;

        // Calculate working days in the month (excluding Sundays only)
        $startDate   = Carbon::createFromDate($year, $month, 1);
        $endDate     = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $workingDays = 0;
        $currentDay  = $startDate->copy();
        while ($currentDay->lte($endDate)) {
            if (!$currentDay->isSunday()) {
                $workingDays++;
            }
            $currentDay->addDay();
        }

        // Count holidays in this month (from holidays table)
        $holidayCount = Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();

        $effectiveWorkDays = max(1, $workingDays - $holidayCount);

        // Count present days from attendance
        $presentDays = Attendance::where('user_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();

        // LOP (Loss of Pay) days
        $lopDays = max(0, $effectiveWorkDays - $presentDays);

        // Per day salary
        $perDaySalary = $baseSalary / $effectiveWorkDays;

        // Deduct LOP
        $lopDeduction = round($perDaySalary * $lopDays, 2);

        // PF Contribution (12% of basic, capped at ₹1800)
        $pfContribution = min(round($baseSalary * 0.12, 2), 1800);

        // Professional Tax (slab based)
        $professionalTax = $baseSalary > 15000 ? 200 : ($baseSalary > 10000 ? 150 : 0);

        $bonuses        = $request->bonuses ?? 0;
        $extraDeductions = $request->extra_deductions ?? 0;
        $totalDeductions = $lopDeduction + $pfContribution + $professionalTax + $extraDeductions;
        $grossEarnings   = $baseSalary + $bonuses;
        $netSalary       = max(0, round($grossEarnings - $totalDeductions, 2));

        Payroll::updateOrCreate(
            ['user_id' => $employee->id, 'month' => $month, 'year' => $year],
            [
                'base_salary'        => $baseSalary,
                'bonuses'            => $bonuses,
                'deductions'         => $totalDeductions,
                'net_salary'         => $netSalary,
                'status'             => 'pending',
                // Store breakdown as JSON in a notes field (add if needed, else store in metadata)
                'present_days'       => $presentDays,
                'working_days'       => $effectiveWorkDays,
                'lop_days'           => $lopDays,
                'pf_contribution'    => $pfContribution,
                'professional_tax'   => $professionalTax,
                'lop_deduction'      => $lopDeduction,
                'extra_deductions'   => $extraDeductions,
            ]
        );

        return redirect()->back()->with('success', "✅ Payroll for {$employee->name} ({$month}/{$year}) auto-generated from attendance.");
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2020|max:2030',
            'base_salary' => 'required|numeric|min:0',
            'deductions'  => 'nullable|numeric|min:0',
            'bonuses'     => 'nullable|numeric|min:0',
        ]);

        $netSalary = $request->base_salary + ($request->bonuses ?? 0) - ($request->deductions ?? 0);

        Payroll::updateOrCreate(
            ['user_id' => $request->user_id, 'month' => $request->month, 'year' => $request->year],
            [
                'base_salary' => $request->base_salary,
                'deductions'  => $request->deductions ?? 0,
                'bonuses'     => $request->bonuses ?? 0,
                'net_salary'  => $netSalary,
                'status'      => 'pending',
            ]
        );

        return redirect()->back()->with('success', '✅ Payroll record generated successfully.');
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
