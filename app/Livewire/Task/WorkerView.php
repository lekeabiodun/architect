<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Models\TaskComment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class WorkerView extends Component
{
    use WithPagination, WithFileUploads;

    public $selectedTask = null;
    public $comment_text = '';
    public $filter_status = '';
    public $media_files = [];
    public $task_status = '';

    public function selectTask($taskId)
    {
        $task = Task::with(['phase.project'])->findOrFail($taskId);
        $this->authorize('view', $task);
        $this->selectedTask = $task;
    }

    public function completeTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('complete', $task);

        $task->status = 'completed';
        $task->actual_end_date = now();
        $task->save();

        // Update phase and project progress
        $task->phase->updateProgress();
        $task->phase->updateStatus();
        
        $this->selectedTask = null;
    }

    public function startTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('complete', $task);

        $task->status = 'in_progress';
        $task->actual_start_date = now();
        $task->save();

        session()->flash('message', 'Task started successfully!');
    }

    public function updateTaskStatus($taskId, $status)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('complete', $task);

        $task->status = $status;
        
        if ($status === 'in_progress' && !$task->actual_start_date) {
            $task->actual_start_date = now();
        } elseif ($status === 'completed') {
            $task->actual_end_date = now();
            // Update phase and project progress
            $task->phase->updateProgress();
            $task->phase->updateStatus();
        }
        
        $task->save();

        session()->flash('message', 'Task status updated successfully!');
    }

    public function addComment($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('view', $task);

        $this->validate([
            'comment_text' => 'required|string|min:3',
            'media_files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200', // 50MB max
        ]);

        // Handle file uploads
        $uploadedFiles = [];
        $mediaType = null;

        if (!empty($this->media_files)) {
            foreach ($this->media_files as $file) {
                $path = $file->store('task-media', 'public');
                $uploadedFiles[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];

                // Determine media type
                if (str_starts_with($file->getMimeType(), 'image/')) {
                    $mediaType = $mediaType === 'video' ? 'mixed' : 'image';
                } elseif (str_starts_with($file->getMimeType(), 'video/')) {
                    $mediaType = $mediaType === 'image' ? 'mixed' : 'video';
                }
            }
        }

        TaskComment::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'comment' => $this->comment_text,
            'media_files' => !empty($uploadedFiles) ? $uploadedFiles : null,
            'media_type' => $mediaType,
        ]);

        $this->comment_text = '';
        $this->media_files = [];
        
        if ($this->selectedTask && $this->selectedTask->id == $taskId) {
            $this->selectedTask->load('comments.user');
        }

        session()->flash('message', 'Comment added successfully!');
    }

    public function render()
    {
        $query = Task::query()
            ->with(['phase.project', 'assignedUser', 'comments.user'])
            ->where('assigned_to', auth()->id());

        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }

        $tasks = $query->orderBy('planned_start_date')->paginate(10);

        // Stats
        $stats = [
            'pending' => Task::where('assigned_to', auth()->id())->where('status', 'pending')->count(),
            'in_progress' => Task::where('assigned_to', auth()->id())->where('status', 'in_progress')->count(),
            'completed' => Task::where('assigned_to', auth()->id())->where('status', 'completed')->count(),
            'total' => Task::where('assigned_to', auth()->id())->count(),
        ];

        return view('livewire.task.worker-view', [
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }
}
