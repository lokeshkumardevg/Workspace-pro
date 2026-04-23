<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoGeneratePayroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate monthly payroll for all employees and send them their salary slips based on fixed 30-day corporate rules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated payroll generation...');
        
        $month = now()->month;
        $year = now()->year;
        
        // Calculate bounds for sandwich logic evaluation
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $extendedStartDate = $startDate->copy()->subDays(5);
        $extendedEndDate = $endDate->copy()->addDays(5);
        
        // Fetch valid employees
        $employees = User::whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Super Admin']))->get();

        $generatedCount = 0;
        
        $csvData = [];
        $csvData[] = ['Employee Name', 'Email', 'Designation', 'Base Monthly Salary', 'Fixed Month Days', 'Payable Days', 'Absent Days', 'Unpaid Leaves', 'Deductions (PF/Tax)', 'Net Payable Salary'];

        foreach ($employees as $employee) {
            $baseSalary = $employee->base_salary ?? 0;
            if ($baseSalary <= 0) {
                continue; // Skip 0 salary config
            }

            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$extendedStartDate->toDateString(), $extendedEndDate->toDateString()])
                ->get()
                ->keyBy('date');

            $monthlyLeaves = LeaveRequest::where('user_id', $employee->id)
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
                                $absentDays++;
                            }
                        } elseif ($attendance && in_array($attendance->status, ['half_day', 'half-day'])) {
                            $absentDays += 0.5;
                        }
                    }
                }
            }

            // 🔹 30-Day Fixed Formula
            $payableDays = max(0, 30 - $absentDays - $unpaidLeaves);
            $perDaySalary = $baseSalary > 0 ? $baseSalary / 30 : 0;
            $basicNet = $perDaySalary * $payableDays;

            $pfContribution = min(round($baseSalary * 0.12, 2), 1800);
            $professionalTax = $baseSalary > 15000 ? 200 : ($baseSalary > 10000 ? 150 : 0);
            $totalDeductions = $pfContribution + $professionalTax; // Skipping manual extras in auto script
            
            $netSalary = max(0, round($basicNet - $totalDeductions, 2));

            $payroll = Payroll::updateOrCreate(
                ['user_id' => $employee->id, 'month' => $month, 'year' => $year],
                [
                    'base_salary'        => $baseSalary,
                    'bonuses'            => 0,
                    'deductions'         => $totalDeductions,
                    'net_salary'         => $netSalary,
                    'status'             => 'pending',
                    'working_days'       => 30,
                    'present_days'       => $payableDays,
                    'lop_days'           => $absentDays,
                    'pf_contribution'    => $pfContribution,
                    'professional_tax'   => $professionalTax,
                    'lop_deduction'      => $unpaidLeaves,
                    'extra_deductions'   => 0,
                ]
            );

            // Automatically emit email inside the loop
            try {
                $employee->notify(new \App\Notifications\SalarySlipNotification($payroll));
            } catch (\Exception $e) {
                Log::error("Automated Payroll Email Failed for {$employee->name}: " . $e->getMessage());
            }

            $csvData[] = [
                $employee->name,
                $employee->email,
                $employee->designation ?? '—',
                $baseSalary,
                30,
                $payableDays,
                $absentDays,
                $unpaidLeaves,
                $totalDeductions,
                $netSalary
            ];

            $generatedCount++;
        }
        
        if (count($csvData) > 1) {
            $file = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            rewind($file);
            $csvContent = stream_get_contents($file);
            fclose($file);

            try {
                $monthName = date("F", mktime(0, 0, 0, $month, 10));
                \Illuminate\Support\Facades\Mail::raw("Hello Admins,\n\nPlease find attached the final auto-generated salary and attendance report for {$monthName} {$year}.\n\nTotal Slips Generated: {$generatedCount}\n\nThanks,\nSystem", function ($message) use ($csvContent, $monthName, $year) {
                    $message->to(['dev.clientg@gmail.com', 'chris@wheedletechnologies.ai'])
                            ->subject("Final Salary & Attendance Report - {$monthName} {$year}");
                    $message->attachData($csvContent, "Salary_Report_{$monthName}_{$year}.csv", [
                        'mime' => 'text/csv',
                    ]);
                });
                $this->info("Successfully sent master CSV report to admins.");
            } catch (\Exception $e) {
                Log::error("Failed to send master CSV report to admins: " . $e->getMessage());
            }
        }

        $this->info("Successfully generated and emailed {$generatedCount} payroll slips for {$month}/{$year}.");
    }
}
