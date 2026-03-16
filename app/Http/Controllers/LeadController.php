<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isPrivileged = $user->hasPermissionTo('view leads') ||
                        $user->roles->whereIn('name', ['Super Admin', 'Admin', 'manager', 'team lead'])->count() > 0;

        $leadsQuery = Lead::with('assignee', 'creator')->orderBy('created_at', 'desc');

        if ($request->search) {
            $leadsQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('company', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if (!$isPrivileged) {
            $leadsQuery->where('assigned_to', $user->id);
        }

        $leads = $leadsQuery->paginate(10)->withQueryString();
        $users = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Employee', 'Admin', 'Super Admin', 'manager', 'team lead', 'HR']))->get();

        // Zoho-style Analytics
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'new')->count(),
            'qualified' => Lead::where('status', 'qualified')->count(),
            'won' => Lead::where('status', 'won')->count(),
            'conversion_rate' => Lead::count() > 0 ? round((Lead::where('status', 'won')->count() / Lead::count()) * 100, 1) : 0,
        ];

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'company' => 'nullable|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        Lead::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company' => $request->company,
            'source' => $request->source,
            'status' => 'new',
            'assigned_to' => $request->assigned_to,
            'created_by' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Lead created successfully');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,won,lost'
        ]);

        $lead->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Lead status updated');
    }
}
