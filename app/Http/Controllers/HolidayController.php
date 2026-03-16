<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->hasPermissionTo('create holidays') || 
                        $user->roles->whereIn('name', ['Super Admin', 'Admin', 'HR', 'manager', 'team lead', 'Manager', 'Team Lead', 'hr', 'admin', 'super admin'])->count() > 0;

        $query = Holiday::orderBy('date', 'asc');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $holidays = $query->paginate(12)->withQueryString();

        return Inertia::render('Holidays/Index', [
            'holidays' => $holidays,
            'filters' => $request->only('search'),
            'canManage' => $isPrivileged,
            'stats' => [
                'upcoming' => Holiday::where('date', '>=', now()->toDateString())->count(),
                'past' => Holiday::where('date', '<', now()->toDateString())->count(),
                'this_month' => Holiday::whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date',
            'description' => 'nullable|string',
        ]);

        Holiday::create($request->all());

        return redirect()->back()->with('success', '✅ Holiday added successfully to the calendar.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'description' => 'nullable|string',
        ]);

        $holiday->update($request->all());

        return redirect()->back()->with('success', '📈 Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->back()->with('success', '🗑️ Holiday removed from the calendar.');
    }
}
