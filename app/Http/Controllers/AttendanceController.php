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

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'isTodayHoliday' => $isTodayHoliday,
            'personalStats' => $personalStats,
            'filters' => $request->only('search'),
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
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->attendance_bypass;

        // 🚫 Block Mobile Devices (Exempting Super Admin)
        if (!$isSuperAdmin) {
            $userAgent = $request->header('User-Agent');
            if (preg_match('/Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                return redirect()->back()->with('error', '❌ Attendance is NOT allowed from mobile devices. Please use a Desktop or Laptop.');
            }
        }

        $lat = $request->lat;
        $lng = $request->lng;

        if (!$isSuperAdmin) {
            // 🛡️ Geofencing Enforcement
            if (!$lat || !$lng) {
                return redirect()->back()->with('error', '❌ Location access is required for attendance.');
            }

            $office = $this->getOfficeConfig();
            $distance = $this->calculateDistance($lat, $lng, $office['lat'], $office['lng']);

            if ($distance > $office['radius']) {
                return redirect()->back()->with('error', sprintf('❌ Outside Office Boundary. You are %.2f meters away.', $distance));
            }

            // 🛡️ IP Enforcement
            if ($user->allowed_ip && $request->ip() !== $user->allowed_ip) {
                return redirect()->back()->with('error', '❌ Attendance not allowed from this IP. Current IP: ' . $request->ip());
            }
        }

        // Holiday Check
        if ($this->holidayService->isHoliday(Carbon::today())) {
            return redirect()->back()->with('info', 'ℹ️ Today is a holiday. Attendance is optional but recorded.');
        }

        $date = Carbon::today();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            ['status' => 'present']
        );

        if (!$attendance->clock_in) {
            $attendance->update([
                'clock_in'    => Carbon::now()->format('H:i:s'),
                'clock_in_ip' => $request->ip(),
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        $msg = $isSuperAdmin && !($lat && $lng)
            ? '✅ Clocked in (Bypassed location check — Super Admin mode.)'
            : '✅ Clocked in successfully from office location.';

        return redirect()->back()->with('success', $msg);
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->attendance_bypass;

        // 🚫 Block Mobile Devices (Exempting Super Admin)
        if (!$isSuperAdmin) {
            $userAgent = $request->header('User-Agent');
            if (preg_match('/Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                return redirect()->back()->with('error', '❌ Attendance is NOT allowed from mobile devices. Please use a Desktop or Laptop.');
            }
        }

        $lat = $request->lat;
        $lng = $request->lng;
        $date = Carbon::today();

        if (!$isSuperAdmin) {
            // 🛡️ Geofencing Enforcement for Clock Out
            if (!$lat || !$lng) {
                return redirect()->back()->with('error', '❌ Location access is required to clock out.');
            }

            $office   = $this->getOfficeConfig();
            $distance = $this->calculateDistance($lat, $lng, $office['lat'], $office['lng']);

            if ($distance > $office['radius']) {
                return redirect()->back()->with('error', sprintf('❌ Outside Office Boundary. You are %.2f meters away.', $distance));
            }

            // 🛡️ IP Enforcement
            if ($user->allowed_ip && $request->ip() !== $user->allowed_ip) {
                return redirect()->back()->with('error', '❌ Attendance not allowed from this IP.');
            }
        }

        $attendance = Attendance::where('user_id', $user->id)->where('date', $date)->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out'    => Carbon::now()->format('H:i:s'),
                'clock_out_ip' => $request->ip(),
                'out_lat'      => $lat,
                'out_lng'      => $lng,
            ]);
        }

        return redirect()->back()->with('success', '👋 Clocked out successfully.');
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

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_export_" . date('Y-m-d') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Employee Email', 'Employee Name', 'Date', 'Clock In', 'Clock Out', 'Status']);

            $attendances = Attendance::with('user')->orderBy('date', 'desc')->get();
            foreach ($attendances as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->user->email,
                    $row->user->name,
                    $row->date,
                    $row->clock_in,
                    $row->clock_out,
                    $row->status
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

        return Inertia::render('Attendance/Calendar', [
            'attendanceData' => $attendanceData,
            'month' => $month,
            'year' => $year,
            'holidays' => $holidays,
            'employees' => $employees,
            'selectedEmployeeId' => (int) $employee_id
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

        $employees = User::whereHas('roles', fn($q) => $q->where('name', 'Employee'))->paginate(3)->withQueryString();
        $reportData = $employees->getCollection()->map(function ($employee) use ($month, $year) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            
            $presentDays = [];
            $absentDays = [];
            
            // Get all attendance for the month
            $monthAttendance = Attendance::where('user_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->keyBy('date');

            $totalWorkingDays = 0;
            $actuallyPresent = 0;

            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->toDateString();
                $isHoliday = $this->holidayService->isHoliday($date);
                $isWeekend = $date->isSaturday() || $date->isSunday();
                
                if (!$isHoliday) {
                    $totalWorkingDays++;
                    if (isset($monthAttendance[$dateStr]) && $monthAttendance[$dateStr]->status === 'present') {
                        $actuallyPresent++;
                    }
                } else {
                    // It's a holiday/weekend. Check Sandwich Rule.
                    if ($isWeekend) {
                        // Check previous Friday (or last working day) and next Monday (or next working day)
                        $friday = $date->copy()->previous(Carbon::FRIDAY);
                        $monday = $date->copy()->next(Carbon::MONDAY);
                        
                        $frAttendance = $monthAttendance[$friday->toDateString()] ?? null;
                        $moAttendance = $monthAttendance[$monday->toDateString()] ?? null;
                        
                        // Sandwich Rule: If both Friday and Monday are NOT 'present', this weekend is ABSENT.
                        // Otherwise, weekends are generally 'present' (Paid Off).
                        if ($frAttendance && $frAttendance->status !== 'present' && $moAttendance && $moAttendance->status !== 'present') {
                            // Sandwich caught! Don't add to present count.
                        } else {
                            $actuallyPresent++; // Paid weekend
                        }
                        $totalWorkingDays++; // Paid weekends count towards total month days in some systems, or stay as is.
                    } else {
                        // Official Holiday (Festivals etc) - counts as Present
                        $actuallyPresent++;
                        $totalWorkingDays++;
                    }
                }
            }

            return [
                'employee' => $employee->name,
                'email' => $employee->email,
                'present_days' => $actuallyPresent,
                'working_days' => $totalWorkingDays,
                'absent_days' => max(0, $totalWorkingDays - $actuallyPresent),
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
