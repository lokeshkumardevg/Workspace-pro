<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('creator')
            ->withCount([
                'tasks as completed_tasks' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->withCount('tasks as total_tasks')
            ->orderBy('id', 'desc');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $projects = $query->paginate(10)->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric',
            'technology_stack' => 'nullable|string',
            'estimated_hours' => 'nullable|integer',
            'team_size' => 'nullable|integer',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        Project::create([
            'name' => $request->name,
            'client_name' => $request->client_name,
            'description' => $request->description,
            'budget' => $request->budget ?? 0,
            'technology_stack' => $request->technology_stack,
            'estimated_hours' => $request->estimated_hours ?? 0,
            'team_size' => $request->team_size ?? 1,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'created_by' => $request->user()->id
        ]);

        return redirect()->back()->with('success', '✅ Project & Business Profile initialized.');
    }

    public function update(Request $request, Project $project)
    {
        if (!$request->user()->hasRole('Super Admin')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric',
            'technology_stack' => 'nullable|string',
            'estimated_hours' => 'nullable|integer',
            'team_size' => 'nullable|integer',
            'proposal_content' => 'nullable|string',
            'handover_notes' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $project->update($request->all());

        return redirect()->back()->with('success', '📈 Project Business logic updated.');
    }

    public function show(Project $project)
    {
        $project->load(['creator', 'tasks.assignee', 'tasks.comments.user']);

        // Calculate progress
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Mocking some financial logic for the report
        // Assuming budget is total, we could track expenses later, but for now show health
        $healthStatus = $progress >= 50 ? 'Healthy' : 'Behind Schedule';

        // Get unique team members who have tasks in this project
        $team = $project->tasks->map(fn($t) => $t->assignee)->filter()->unique('id')->values();

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'stats' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress' => $progress,
                'health' => $healthStatus,
                'team' => $team
            ]
        ]);
    }
}
