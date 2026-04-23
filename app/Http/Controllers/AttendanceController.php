<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    private $holidayService;

    private function getOfficeConfig()
    {
        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
        return [
            'lat' => (float) (!empty($settings['office_lat']) ? $settings['office_lat'] : 28.61314773529335),
            'lng' => (float) (!empty($settings['office_lng']) ? $settings['office_lng'] : 77.38732458230429),
            'radius' => (int) (!empty($settings['office_radius']) ? $settings['office_radius'] : 200),
        ];
    }

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Attendance::with('user')->orderBy('date', 'desc');

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        // Employees only see their own
        $isPrivileged = $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'Manager'])->count() > 0;
        if (!$isPrivileged) {
            $query->where('user_id', $user->id);
        }

        $attendances = $query->paginate(8)->withQueryString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();

        // Check if today is a holiday
        $isTodayHoliday = $this->holidayService->isHoliday(Carbon::today());

        // Personal Monthly Stats
        $month = now()->month;
        $year = now()->year;
        $personalStats = [
            'present' => Attendance::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->where('status', 'present')
                ->count(),
            'working_days' => $this->holidayService->getWorkingDaysCount($month, $year)
        ];

        $office = $this->getOfficeConfig();
        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'isTodayHoliday' => $isTodayHoliday,
            'personalStats' => $personalStats,
            'filters' => $request->only('search'),
            'shiftStartTime' => $settings['shift_start_time'] ?? '09:00:00',
            'shiftEndTime' => $settings['shift_end_time'] ?? '18:00:00',
            'officeLocation' => [
                'lat' => $office['lat'],
                'lng' => $office['lng'],
                'radius' => $office['radius']
            ]
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = $request->user();
        $isBypass = $user->hasRole('Super Admin') || $user->attendance_bypass;

        // 🚫 Block Mobile Devices (Exempting Bypass Users)
        if (!$isBypass) {
            $userAgent = $request->header('User-Agent');
            if (preg_match('/Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                return redirect()->back()->with('error', '❌ Attendance is NOT allowed from mobile devices. Please use a Desktop or Laptop.');
            }
        }

        $lat = $request->lat;
        $lng = $request->lng;

        $isWfh = $request->boolean('is_wfh');
        $wfhReason = $request->input('wfh_reason');

        if (!$isWfh) {
            // ✅ Lat/Lng is required for ALL users (including bypass) — to save location data
            if (!$lat || !$lng) {
                if (!$isBypass) {
                    // Non-bypass users: strictly block
                    return redirect()->back()->with('error', '❌ Location access is required for attendance.');
                }
                // Bypass users without location: allow but note it
            } else {
                // ✅ Always check geofencing — but only BLOCK non-bypass users
                $office = $this->getOfficeConfig();
                $distance = $this->calculateDistance($lat, $lng, $office['lat'], $office['lng']);

                if ($distance > $office['radius'] && !$isBypass) {
                    return redirect()->back()->with('error', sprintf('❌ Outside Office Boundary. You are %.2f meters away.', $distance));
                }

                // 🛡️ IP Enforcement (non-bypass only)
                if (!$isBypass && $user->allowed_ip && $request->ip() !== $user->allowed_ip) {
                    return redirect()->back()->with('error', '❌ Attendance not allowed from this IP. Current IP: ' . $request->ip());
                }
            }
        }

        // Holiday Check
        if ($this->holidayService->isHoliday(Carbon::today())) {
            if (!$isBypass) {
                return redirect()->back()->with('error', '❌ Today is a holiday. You cannot mark attendance.');
            }
        }

        $date = Carbon::today();
        
        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
        $shiftStartTime = $settings['shift_start_time'] ?? '09:30:00';

        // Status determination
        $status = 'present';
        if ($isWfh) {
            $status = 'half_day';
        } elseif (Carbon::now()->format('H:i:s') > $shiftStartTime) {
            $status = 'late';
        }

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            ['status' => $status]
        );

        if (!$attendance->clock_in) {
            $attendance->update([
                'clock_in'    => Carbon::now()->format('H:i:s'),
                'clock_in_ip' => $request->ip(),
                'lat'         => $lat ?: null,
                'lng'         => $lng ?: null,
                'is_wfh'      => $isWfh,
                'wfh_reason'  => $wfhReason,
                'status'      => $status,
            ]);
        }

        $msg = $isWfh 
            ? '✅ Clocked in as Work From Home (Half Day).'
            : ($isBypass && !($lat && $lng)
                ? '✅ Clocked in (Bypass mode — no location provided.)'
                : '✅ Clocked in successfully.');

        return redirect()->back()->with('success', $msg);
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $isBypass = $user->hasRole('Super Admin') || $user->attendance_bypass;

        // 🚫 Block Mobile Devices (Exempting Bypass Users)
        if (!$isBypass) {
            $userAgent = $request->header('User-Agent');
            if (preg_match('/Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                return redirect()->back()->with('error', '❌ Attendance is NOT allowed from mobile devices. Please use a Desktop or Laptop.');
            }
        }

        $lat = $request->lat;
        $lng = $request->lng;
        $date = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)->where('date', $date)->first();

        if (!$attendance || !$attendance->clock_in) {
            return redirect()->back()->with('error', '❌ You have not clocked in today.');
        }

        if ($attendance->clock_out) {
            return redirect()->back()->with('error', '❌ You have already clocked out today.');
        }

        // Check if attendance exists and is WFH
        $isWfh = $attendance->is_wfh;

        if (!$isWfh) {
            // ✅ Collect lat/lng for ALL users — but only BLOCK non-bypass users if outside boundary
            if (!$lat || !$lng) {
                if (!$isBypass) {
                    return redirect()->back()->with('error', '❌ Location access is required to clock out.');
                }
                // Bypass users: allow without location
            } else {
                // ✅ Run geofencing check — but only BLOCK non-bypass users
                $office   = $this->getOfficeConfig();
                $distance = $this->calculateDistance($lat, $lng, $office['lat'], $office['lng']);

                if ($distance > $office['radius'] && !$isBypass) {
                    return redirect()->back()->with('error', sprintf('❌ Outside Office Boundary. You are %.2f meters away.', $distance));
                }

                // 🛡️ IP Enforcement (non-bypass only)
                if (!$isBypass && $user->allowed_ip && $request->ip() !== $user->allowed_ip) {
                    return redirect()->back()->with('error', '❌ Attendance not allowed from this IP.');
                }
            }
        }

        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
        $shiftEndTime = $settings['shift_end_time'] ?? '18:30:00';

        $attendance->update([
            'clock_out'    => Carbon::now()->format('H:i:s'),
            'clock_out_ip' => $request->ip(),
            'out_lat'      => $lat ?: null,
            'out_lng'      => $lng ?: null,
        ]);
        
        $msg = '👋 Clocked out successfully.';
        if (Carbon::now()->format('H:i:s') < $shiftEndTime) {
            $msg = '⚠️ Clocked out early. Status has been flagged.';
            // Enforcing half_day dynamically as requested
            $attendance->update(['status' => 'half_day']);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Admin can edit attendance.');
        }

        $request->validate([
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s',
            'status' => 'required|in:present,absent,late,half_day',
            'date' => 'required|date'
        ]);

        $attendance->update($request->only(['clock_in', 'clock_out', 'status', 'date']));

        return redirect()->back()->with('success', '✅ Attendance record updated successfully.');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|string',
            'clock_out' => 'nullable|string',
            'status' => 'required|in:present,absent,late,half_day,half-day'
        ]);

        Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'status' => $request->status
            ]
        );

        return redirect()->back()->with('success', '✅ Attendance record saved successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        $attendance->delete();

        return redirect()->back()->with('success', '🗑️ Attendance record deleted successfully.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_report_{$year}_{$month}.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($month, $year) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee ID', 'Employee Name', 'Email', 'Total Month Days', 'Total Working Days', 'Weekends (Sat/Sun)', 'Holidays', 'Present Days', 'Approved Leaves', 'Absent Days', 'WFH / Half Days', 'Late Days', 'Total Paid Days']);

            $employees = \App\Models\User::whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Admin']))->get();
            
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            $monthlyHolidays = \App\Models\Holiday::whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->toArray();

            $extendedStartDate = $startDate->copy()->subDays(5);
            $extendedEndDate = $endDate->copy()->addDays(5);

            foreach ($employees as $employee) {
                $monthAttendance = Attendance::where('user_id', $employee->id)
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
                $holidays = 0;
                $present = 0;
                $absent = 0;
                $halfDays = 0; // WFH or Half Day
                $late = 0;
                $leaveDays = 0;
                $sandwichAbsent = 0;

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
                        $holidays++;
                    } elseif ($isWeekend) {
                        $weekends++;
                        
                        // Sandwich Rule Check (Ignore if Weekend is already covered by Leave)
                        if (!$isLeaveDate) {
                            $friday = $date->copy()->previous(Carbon::FRIDAY);
                            $monday = $date->copy()->next(Carbon::MONDAY);
                            $frAttendance = $monthAttendance[$friday->toDateString()] ?? null;
                            $moAttendance = $monthAttendance[$monday->toDateString()] ?? null;
                            
                            $fridayLeave = false;
                            $mondayLeave = false;
                            foreach ($monthlyLeaves as $l) {
                                if ($friday->between($l->from_date, $l->to_date)) $fridayLeave = true;
                                if ($monday->between($l->from_date, $l->to_date)) $mondayLeave = true;
                            }

                            $isFridayAbsent = (!$frAttendance || $frAttendance->status === 'absent') && !$fridayLeave;
                            $isMondayAbsent = (!$moAttendance || $moAttendance->status === 'absent') && !$mondayLeave;

                            if ($isFridayAbsent && $isMondayAbsent) {
                                $absent++; // Weekend becomes absent due to sandwich rule
                                $sandwichAbsent++;
                            }
                        }
                    } elseif ($isHolidayDB) {
                        $holidays++;
                    } else {
                        $workingDays++;
                        
                        $attendance = $monthAttendance[$dateStr] ?? null;
                        if ($attendance) {
                            if ($attendance->status === 'present') {
                                $present++;
                            } elseif ($attendance->status === 'late') {
                                $present++;
                                $late++;
                            } elseif (in_array($attendance->status, ['half_day', 'half-day'])) {
                                $present += 0.5;
                                $absent += 0.5; // Half Day / WFH logic
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

                fputcsv($file, [
                    $employee->id,
                    $employee->name,
                    $employee->email,
                    $monthDays,
                    $workingDays,
                    $weekends,
                    $holidays,
                    $present,
                    $leaveDays,
                    $absent,
                    $halfDays,
                    $late,
                    ($present + $weekends + $holidays + $leaveDays - $sandwichAbsent)
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        // Skip header
        fgetcsv($handle);

        $count = 0;
        \DB::transaction(function() use ($handle, &$count) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $email = $data[1] ?? null;
                $date = $data[3] ?? null;
                $clock_in = $data[4] ?? null;
                $clock_out = $data[5] ?? null;
                $status = $data[6] ?? 'present';

                if ($email && $date) {
                    $employee = User::where('email', $email)->first();
                    if ($employee) {
                        Attendance::updateOrCreate(
                            ['user_id' => $employee->id, 'date' => $date],
                            [
                                'clock_in' => !empty($clock_in) ? $clock_in : null,
                                'clock_out' => !empty($clock_out) ? $clock_out : null,
                                'status' => $status
                            ]
                        );
                        $count++;
                    }
                }
            }
        });
        
        fclose($handle);

        return redirect()->back()->with('success', "✅ Successfully imported $count attendance records.");
    }

    /**
     * Calendar View for Employee
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'Admin', 'HR', 'Manager', 'Team Lead', 'manager', 'team lead']) || $user->can('view attendance');
        
        $employee_id = $request->input('employee_id', $user->id);
        if (!$isSuperAdmin && $employee_id != $user->id) {
            abort(403);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $attendanceData = Attendance::where('user_id', $employee_id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->keyBy('date');

        // Extract Holidays for calendar
        $holidays = \App\Models\Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->pluck('date')
            ->toArray();

        // Always send employees list so dropown always works
        $employees = User::orderBy('name')->get(['id', 'name']);

        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');

        return Inertia::render('Attendance/Calendar', [
            'attendanceData' => $attendanceData,
            'month' => $month,
            'year' => $year,
            'holidays' => $holidays,
            'employees' => $employees,
            'selectedEmployeeId' => (int) $employee_id,
            'shiftStartTime' => $settings['shift_start_time'] ?? '09:00:00',
            'shiftEndTime' => $settings['shift_end_time'] ?? '18:00:00',
        ]);
    }

    /**
     * Report for HR
     */
    public function report(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->hasPermissionTo('download reports') || 
                        $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'manager', 'team lead'])->count() > 0;
                        
        if (!$isPrivileged) {
            abort(403, 'Unauthorized');
        }

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $monthlyHolidays = \App\Models\Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $extendedStartDate = $startDate->copy()->subDays(5);
        $extendedEndDate = $endDate->copy()->addDays(5);

        $employees = User::whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Admin']))->paginate(3)->withQueryString();
        $reportData = $employees->getCollection()->map(function ($employee) use ($month, $year, $startDate, $endDate, $monthlyHolidays, $extendedStartDate, $extendedEndDate) {
                
            $monthAttendance = Attendance::where('user_id', $employee->id)
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
            $holidays = 0;
            $present = 0;
            $absent = 0;
            $leaveDays = 0;
            $halfDays = 0;
            $sandwichAbsent = 0;

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
                    $holidays++;
                } elseif ($isWeekend) {
                    $weekends++;
                    // Sandwich Rule Check
                    if (!$isLeaveDate) {
                        $friday = $date->copy()->previous(Carbon::FRIDAY);
                        $monday = $date->copy()->next(Carbon::MONDAY);
                        $frAttendance = $monthAttendance[$friday->toDateString()] ?? null;
                        $moAttendance = $monthAttendance[$monday->toDateString()] ?? null;
                        
                        $fridayLeave = false;
                        $mondayLeave = false;
                        foreach ($monthlyLeaves as $l) {
                            if ($friday->between($l->from_date, $l->to_date)) $fridayLeave = true;
                            if ($monday->between($l->from_date, $l->to_date)) $mondayLeave = true;
                        }

                        $isFridayAbsent = (!$frAttendance || $frAttendance->status === 'absent') && !$fridayLeave;
                        $isMondayAbsent = (!$moAttendance || $moAttendance->status === 'absent') && !$mondayLeave;

                        if ($isFridayAbsent && $isMondayAbsent) {
                            $absent++; // Sandwich rule absent
                            $sandwichAbsent++;
                        }
                    }
                } elseif ($isHolidayDB) {
                    $holidays++;
                } else {
                    $workingDays++;
                    
                    $attendance = $monthAttendance[$dateStr] ?? null;
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

            return [
                'employee' => $employee->name,
                'email' => $employee->email,
                'month_days' => $monthDays,
                'working_days' => $workingDays,
                'weekends' => $weekends,
                'holidays' => $holidays,
                'present_days' => $present,
                'absent_days' => $absent,
                'leave_days' => $leaveDays,
                'half_days' => $halfDays,
                'total_paid_days' => ($present + $weekends + $holidays + $leaveDays - $sandwichAbsent),
            ];
        });

        $employees->setCollection($reportData);

        return Inertia::render('Attendance/Report', [
            'report' => $employees,
            'filters' => ['month' => (int) $month, 'year' => (int) $year],
        ]);
    }

    /**
     * Calculate Distance between two lat/lng points in meters (Haversine Formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
