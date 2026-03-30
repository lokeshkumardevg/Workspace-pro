<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('user')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('id', 'desc')
            ->paginate(3);

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create announcements') && !auth()->user()->hasRole(['Super Admin', 'Admin', 'HR', 'manager', 'team lead', 'Manager', 'Team Lead', 'hr', 'admin', 'super admin'])) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,alert,event',
            'expires_at' => 'nullable|date',
        ]);

        $announcement = Announcement::create([
            'user_id' => auth()->id(),
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'type' => $request->input('type'),
            'expires_at' => $request->input('expires_at'),
        ]);

        try {
            $users = \App\Models\User::pluck('email')->filter()->toArray();
            if (!empty($users)) {
                $title = $request->input('title');
                $content = $request->input('content');
                \Illuminate\Support\Facades\Mail::raw("A new announcement has been posted:\n\nTitle: {$title}\n\n{$content}", function ($message) use ($users, $title) {
                    $message->bcc($users)
                        ->subject('New Announcement: ' . $title);
                });
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send announcement email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', '📢 Announcement broadcasted successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        if (!auth()->user()->hasPermissionTo('create announcements') && !auth()->user()->hasRole(['Super Admin', 'Admin', 'HR', 'manager', 'team lead', 'Manager', 'Team Lead', 'hr', 'admin', 'super admin'])) {
            abort(403);
        }

        $announcement->update(['is_active' => false]);
        return redirect()->back()->with('success', '🗑️ Announcement removed.');
    }
}
