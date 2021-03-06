<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Tag;
use App\Task;
use App\TaskStatus;
use App\User;
use App\Filters\TaskFilters;
use Illuminate\Http\Request;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TaskFilters $filters)
    {
        $tasks = $this->getTasks($filters);
        $statuses = TaskStatus::get(['name', 'id']);
        $executors = User::get(['name', 'id']);
        $tags = Tag::get(['name', 'id']);
        return view('tasks.index', compact('tasks', 'statuses', 'executors', 'tags'));
    }

    protected function getTasks(TaskFilters $filters)
    {
        $threads = Task::with([
            'tags',
            'status',
            'executor'
        ])->latest()->filter($filters);
        return $threads->paginate(10);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $statuses = TaskStatus::all('id', 'name');
        $users = User::all('id', 'name');
        $tags = Tag::all('id', 'name');
        return view('tasks.create', compact('statuses', 'users', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(TaskRequest $request)
    {
        $taskStatus = TaskStatus::find($request->input('status_id'));
        $user = auth()->user();
        $task = $user->tasks()->create($request->all());
        $task->status()->associate($taskStatus)->executor()->associate($request->input('executor_id'))->save();
        $task->syncTags($request->input('tags') ?? []);
        session()->flash('notifications', 'Task Created');
        return redirect(route('tasks.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Task $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        $status = $task->status;
        $executor = $task->executor;
        $tags = $task->tags;
        $creator = $task->user;
        return view('tasks.show', compact('task', 'status', 'executor', 'tags', 'creator'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Task $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        $status = $task->status;
        $executor = $task->executor;
        $freeStatuses = TaskStatus::except($status->id)->get(['name', 'id']);
        $freeUsers = User::except($executor->id)->get(['name', 'email', 'id']);
        $tags = $task->tags;
        return view('tasks.edit', compact('task', 'status', 'executor', 'freeStatuses', 'freeUsers', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Task $task
     * @return \Illuminate\Http\Response
     */
    public function update(TaskRequest $request, Task $task)
    {
        $taskStatus = TaskStatus::find($request->input('status_id'));
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        $task->status()->associate($taskStatus)
            ->executor()->associate($request->input('executor_id'))
            ->save();
        session()->flash('notifications', 'Task Updated');
        $task->syncTags($request->input('tags', []));
        return redirect(route('tasks.index'));
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect(route('tasks.index'));
    }
}
