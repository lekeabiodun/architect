<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Models\TaskComment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class InspectorView extends Component
{
    use WithPagination, WithFileUploads;

    public $selectedTask = null;
    public $comment_text = '';
    public $filter_status = '';
    public $filter_inspection = '';
    public $media_files = [];
    public $inspection_status = '';
    public $inspection_feedback = '';
    public $task_status = '';

    public function selectTask($taskId)
    {
        $task = Task::with(['phase.project', 'assignedUser', 'inspector'])->findOrFail($taskId);
        $this->authorize('inspect', $task);
        $this->selectedTask = $task;
    }

    public function updateTaskStatus($taskId, $status)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('inspect', $task);

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

    public function inspectTask($taskId, $status)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('inspect', $task);

        $this->validate([
            'inspection_feedback' => 'required|string|min:10',
        ]);

        if ($status === 'passed') {
            $task->approveInspection(auth()->user(), $this->inspection_feedback);
            session()->flash('message', 'Task inspection passed!');
        } else {
            $task->failInspection(auth()->user(), $this->inspection_feedback, true);
            session()->flash('message', 'Task inspection failed. Worker has been notified.');
        }

        $this->inspection_feedback = '';
        $this->selectedTask = null;
    }

    public function addComment($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('inspect', $task);

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
            ->with(['phase.project', 'assignedUser', 'inspector', 'comments.user'])
            ->whereHas('phase.project.users', function ($q) {
                $q->where('user_id', auth()->id());
            });

        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_inspection) {
            $query->where('inspection_status', $this->filter_inspection);
        }

        $tasks = $query->orderBy('planned_start_date')->paginate(10);

        // Stats
        $stats = [
            'total' => Task::whereHas('phase.project.users', function ($q) {
                $q->where('user_id', auth()->id());
            })->count(),
            'pending_inspection' => Task::whereHas('phase.project.users', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('status', 'completed')
              ->whereIn('inspection_status', ['pending', 're_inspection', null])
              ->count(),
            'passed' => Task::whereHas('phase.project.users', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('inspection_status', 'passed')->count(),
            'failed' => Task::whereHas('phase.project.users', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('inspection_status', 'failed')->count(),
        ];

        return view('livewire.task.inspector-view', [
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }
}
