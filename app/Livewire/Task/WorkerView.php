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
    public $progress_comment = '';
    public $filter_status = '';
    public $progress_media = [];
    public $task_status = '';
    public $showProgressModal = false;
    public $showDetailsModal = false;

    public function selectTask($taskId)
    {
        $task = Task::with([
            'phase.project',
            'predecessor',
            'inspector',
            'comments.user',
        ])->findOrFail($taskId);
        $this->authorize('view', $task);
        $this->selectedTask = $task;
    }

    public function openProgressModal($taskId)
    {
        $this->resetProgressForm();
        $this->selectTask($taskId);
        $this->showProgressModal = true;
    }

    public function openDetailsModal($taskId)
    {
        $this->selectTask($taskId);
        $this->showDetailsModal = true;
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

    public function saveTaskProgress()
    {
        if (!$this->selectedTask) {
            return;
        }

        $this->authorize('view', $this->selectedTask);

        $this->validate([
            'progress_comment' => 'nullable|string',
            'progress_media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,wmv|max:51200',
        ]);

        if (!$this->progress_comment && empty($this->progress_media)) {
            $this->addError('progress_comment', 'Add a comment or attach at least one media file.');
            return;
        }

        $uploadedFiles = [];

        if (!empty($this->progress_media)) {
            foreach ($this->progress_media as $file) {
                $path = $file->store('task-media', 'public');
                $uploadedFiles[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        TaskComment::create([
            'task_id' => $this->selectedTask->id,
            'user_id' => auth()->id(),
            'comment' => $this->progress_comment,
            'media_files' => !empty($uploadedFiles) ? $uploadedFiles : null,
            'media_type' => $this->determineMediaType($uploadedFiles),
        ]);

        $this->resetProgressForm();

        $this->selectedTask->load('comments.user');

        session()->flash('message', 'Progress update added successfully!');
    }

    public function closeProgressModal()
    {
        $this->showProgressModal = false;
        $this->resetProgressForm();
        $this->selectedTask = null;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
    }

    private function resetProgressForm()
    {
        $this->progress_comment = '';
        $this->progress_media = [];
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function determineMediaType(array $mediaFiles): ?string
    {
        if (empty($mediaFiles)) {
            return null;
        }

        $types = collect($mediaFiles)
            ->map(function ($file) {
                $mime = $file['mime_type'] ?? '';

                return str_starts_with($mime, 'image/')
                    ? 'image'
                    : (str_starts_with($mime, 'video/') ? 'video' : 'file');
            })
            ->unique()
            ->values();

        return $types->count() === 1 ? $types->first() : 'mixed';
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
