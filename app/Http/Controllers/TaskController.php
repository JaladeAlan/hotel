<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'task_description' => 'required|string',
            'task_start' => 'required|date',
            'task_end' => 'required|date',
        ]);

        $task = Task::create($request->all());
        return response()->json($task, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|in:pending,completed']);

        $task = Task::findOrFail($id);
        $task->update(['status' => $request->status]);

        return response()->json($task);
    }
}
