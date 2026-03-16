<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $attendances = $query->paginate(15)->withQueryString();

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
        $lat = $request->lat;
        $lng = $request->lng;

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
                'clock_in' => Carbon::now()->format('H:i:s'),
                'clock_in_ip' => $request->ip(),
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        return redirect()->back()->with('success', '✅ Clocked in successfully from office location.');
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $lat = $request->lat;
        $lng = $request->lng;
        $date = Carbon::today();

        // 🛡️ Geofencing Enforcement for Clock Out
        if (!$lat || !$lng) {
            return redirect()->back()->with('error', '❌ Location access is required to clock out.');
        }

        $office = $this->getOfficeConfig();
        $distance = $this->calculateDistance($lat, $lng, $office['lat'], $office['lng']);

        if ($distance > $office['radius']) {
            return redirect()->back()->with('error', sprintf('❌ Outside Office Boundary. You are %.2f meters away.', $distance));
        }

        // 🛡️ IP Enforcement
        if ($user->allowed_ip && $request->ip() !== $user->allowed_ip) {
            return redirect()->back()->with('error', '❌ Attendance not allowed from this IP.');
        }

        $attendance = Attendance::where('user_id', $user->id)->where('date', $date)->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => Carbon::now()->format('H:i:s'),
                'clock_out_ip' => $request->ip(),
                'out_lat' => $lat,
                'out_lng' => $lng,
            ]);
        }

        return redirect()->back()->with('success', '👋 Clocked out successfully from office boundary.');
    }

    /**
     * Report for HR
     */
    public function report(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR'])->count() > 0;

        if (!$isPrivileged) {
            abort(403, 'Unauthorized');
        }

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $employees = User::role('Employee')->get();
        $reportData = [];

        foreach ($employees as $employee) {
            $presentCount = Attendance::where('user_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->where('status', 'present')
                ->count();

            $totalWorkingDays = $this->holidayService->getWorkingDaysCount($month, $year);

            $reportData[] = [
                'employee' => $employee->name,
                'email' => $employee->email,
                'present_days' => $presentCount,
                'working_days' => $totalWorkingDays,
                'absent_days' => max(0, $totalWorkingDays - $presentCount),
            ];
        }

        return Inertia::render('Attendance/Report', [
            'report' => $reportData,
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
