<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use App\Models\Project;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Announcement;

class AIController extends Controller
{
    public function chat(Request $request)
    {
        $messages = $request->input('messages', []);

        // Define system instructions
        $systemMessage = [
            'role' => 'system',
            'content' => "You are 'Brain', a highly capable AI assistant built into Wheedle Technologies task management system. Follow these rules: 
1. If the user asks you to create a task, project, leave, or announcement, use the appropriate tool. Give them a friendly summary of what you did.
2. If the user searches for tasks or projects, use the tool. If the tool returns empty, say no records were found.
3. Keep responses conversational, concise, and helpful. Use formatting."
        ];

        // Ensure system prompt is first
        if (empty($messages) || $messages[0]['role'] !== 'system') {
            array_unshift($messages, $systemMessage);
        }

        $tools = $this->getToolsSchema();

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o', // Or gpt-3.5-turbo if 4o is too slow
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to connect to OpenAI.', 'details' => $response->body()], 500);
        }

        $responseData = $response->json();
        $message = $responseData['choices'][0]['message'] ?? null;

        if (!$message) {
            return response()->json(['error' => 'No response from OpenAI'], 500);
        }

        // Handle tool calls if the AI decides an action is needed
        if (!empty($message['tool_calls'])) {
            $toolResults = [];

            foreach ($message['tool_calls'] as $toolCall) {
                $functionName = $toolCall['function']['name'];
                $arguments = json_decode($toolCall['function']['arguments'], true);

                $actionResult = $this->executeTool($functionName, $arguments);

                $toolResults[] = [
                    'tool_call_id' => $toolCall['id'],
                    'role'         => 'tool',
                    'name'         => $functionName,
                    'content'      => json_encode($actionResult),
                ];
            }

            // We need to send the tool results back to OpenAI to get a final conversational summary
            $messages[] = $message; // Append the assistant's tool call request
            $messages = array_merge($messages, $toolResults);

            $finalResponse = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => $messages,
                ]);

            if ($finalResponse->successful()) {
                $finalMessage = $finalResponse->json()['choices'][0]['message'];
                return response()->json(['message' => $finalMessage]);
            }
        }

        return response()->json(['message' => $message]);
    }

    private function getToolsSchema()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_task',
                    'description' => 'Create a new task in the system.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format'],
                            'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                            'status' => ['type' => 'string', 'enum' => ['pending', 'in_progress', 'completed']]
                        ],
                        'required' => ['title']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_project',
                    'description' => 'Create a new project in the system.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'estimated_hours' => ['type' => 'integer']
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'request_leave',
                    'description' => 'Create a leave request for the currently authenticated user.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'from_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format'],
                            'to_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format'],
                            'reason' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'enum' => ['annual', 'sick', 'casual', 'maternity', 'paternity', 'unpaid']]
                        ],
                        'required' => ['from_date', 'to_date', 'reason']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_announcement',
                    'description' => 'Broadcast a new announcement to all employees.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => ['low', 'normal', 'high']]
                        ],
                        'required' => ['title', 'message']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_tasks',
                    'description' => 'Search tasks assigned to the current user.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => ['type' => 'string', 'description' => 'Optional search keyword to filter tasks']
                        ]
                    ]
                ]
            ]
        ];
    }

    private function executeTool($name, $arguments)
    {
        $user = Auth::user();

        try {
            switch ($name) {
                case 'create_task':
                    $task = Task::create([
                        'title' => $arguments['title'],
                        'description' => $arguments['description'] ?? null,
                        'due_date' => $arguments['due_date'] ?? null,
                        'priority' => $arguments['priority'] ?? 'medium',
                        'status' => $arguments['status'] ?? 'pending',
                        'assigned_to' => $user->id,
                        'created_by' => $user->id,
                    ]);
                    return ['status' => 'success', 'message' => "Task #{$task->id} created successfully.", 'task' => $task];

                case 'create_project':
                    $project = Project::create([
                        'name' => $arguments['name'],
                        'description' => $arguments['description'] ?? null,
                        'estimated_hours' => $arguments['estimated_hours'] ?? null,
                        'created_by' => $user->id,
                        'status' => 'active'
                    ]);
                    return ['status' => 'success', 'message' => "Project #{$project->id} created successfully.", 'project' => $project];

                case 'request_leave':
                     // Determine Leave Type internal mapping
                     $legacyTypes = ['annual' => 'Annual Leave', 'sick' => 'Sick Leave', 'casual' => 'Casual Leave'];
                     $typeLabel = $legacyTypes[$arguments['type'] ?? 'casual'] ?? 'Casual Leave';
                     
                     // Get a random available leave type just to stick it in constraint
                     $leaveType = LeaveType::where('name', 'like', "%{$arguments['type']}%")->first();
                     
                     $from = \Carbon\Carbon::parse($arguments['from_date']);
                     $to = \Carbon\Carbon::parse($arguments['to_date']);
                     $days = $from->diffInDays($to) + 1;

                    $leave = LeaveRequest::create([
                        'user_id' => $user->id,
                        'type' => $leaveType ? $leaveType->id : null,
                        // If it fails foreign key, it might need string based on older schema. Task management usually stores ID. (Wait, checking schema later).
                        'from_date' => $from->toDateString(),
                        'to_date' => $to->toDateString(),
                        'reason' => $arguments['reason'],
                        'days' => $days,
                        'status' => 'pending'
                    ]);
                    return ['status' => 'success', 'message' => "Leave requested successfully.", 'leave' => $leave];

                case 'create_announcement':
                    $announcement = Announcement::create([
                        'title' => $arguments['title'],
                        'message' => $arguments['message'],
                        'priority' => $arguments['priority'] ?? 'normal',
                        'created_by' => $user->id
                    ]);
                    return ['status' => 'success', 'message' => "Announcement broadcast successfully.", 'announcement' => $announcement];

                case 'search_tasks':
                    $query = Task::where('assigned_to', $user->id);
                    if (!empty($arguments['keyword'])) {
                        $query->where('title', 'like', "%{$arguments['keyword']}%");
                    }
                    $tasks = $query->take(5)->get(['id', 'title', 'status', 'due_date']);
                    return ['status' => 'success', 'results' => $tasks];

                default:
                    return ['status' => 'error', 'message' => 'Unknown tool requested.'];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
