<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'comment' => 'required|string',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('task_attachments', 'public');
        }

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
            'attachment' => $attachmentPath,
        ]);

        return redirect()->back()->with('success', 'Message sent to team.');
    }
}
