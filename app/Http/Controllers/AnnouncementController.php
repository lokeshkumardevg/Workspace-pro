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
            ->get();

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Admin', 'HR'])) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,alert,event',
            'expires_at' => 'nullable|date',
        ]);

        Announcement::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->back()->with('success', '📢 Announcement broadcasted successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Admin', 'HR'])) {
            abort(403);
        }

        $announcement->update(['is_active' => false]);
        return redirect()->back()->with('success', '🗑️ Announcement removed.');
    }
}
