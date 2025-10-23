<?php

namespace App\Livewire\Inspector;

use App\Models\Task;
use App\Models\MaterialRequest;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $selectedTask = null;
    public $inspection_feedback = '';
    public $filter_project = '';

    public function approveTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('inspect', $task);

        $task->approveInspection(auth()->user(), $this->inspection_feedback);
        
        $this->selectedTask = null;
        $this->inspection_feedback = '';
    }

    public function failTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('inspect', $task);

        $this->validate([
            'inspection_feedback' => 'required|string|min:10',
        ]);

        $task->failInspection(auth()->user(), $this->inspection_feedback, true);
        
        $this->selectedTask = null;
        $this->inspection_feedback = '';
    }

    public function selectTask($taskId)
    {
        $this->selectedTask = $taskId;
        $this->inspection_feedback = '';
    }

    public function confirmMaterialDelivery($requestId)
    {
        $request = MaterialRequest::findOrFail($requestId);
        $this->authorize('confirm', $request);

        $this->validate([
            'inspection_feedback' => 'required|string',
        ]);

        $request->confirm(auth()->user(), $this->inspection_feedback);
        
        $this->inspection_feedback = '';
    }

    public function render()
    {
        // Get projects user is assigned to
        $user = auth()->user();
        $projectIds = $user->projects()->pluck('projects.id');

        // Get tasks requiring inspection
        $tasksQuery = Task::query()
            ->with(['phase.project', 'assignedUser', 'inspector'])
            ->whereHas('phase.project', function($q) use ($projectIds) {
                $q->whereIn('id', $projectIds);
            })
            ->where('status', 'completed')
            ->whereIn('inspection_status', ['pending', 're_inspection', null]);

        if ($this->filter_project) {
            $tasksQuery->whereHas('phase.project', function($q) {
                $q->where('id', $this->filter_project);
            });
        }

        $pendingTasks = $tasksQuery->latest('actual_end_date')->paginate(10);

        // Get material requests awaiting confirmation
        $pendingMaterials = MaterialRequest::query()
            ->with(['material', 'project', 'requester', 'disburser'])
            ->whereIn('project_id', $projectIds)
            ->where('status', 'disbursed')
            ->latest()
            ->take(5)
            ->get();

        // Get inspection stats
        $stats = [
            'pending' => Task::whereHas('phase.project', function($q) use ($projectIds) {
                $q->whereIn('id', $projectIds);
            })->where('status', 'completed')->whereIn('inspection_status', ['pending', 're_inspection', null])->count(),
            
            'passed_today' => Task::whereHas('phase.project', function($q) use ($projectIds) {
                $q->whereIn('id', $projectIds);
            })->where('inspected_by', $user->id)->where('inspection_status', 'passed')->whereDate('inspected_at', today())->count(),
            
            'failed_today' => Task::whereHas('phase.project', function($q) use ($projectIds) {
                $q->whereIn('id', $projectIds);
            })->where('inspected_by', $user->id)->where('inspection_status', 'failed')->whereDate('inspected_at', today())->count(),
            
            'materials_pending' => MaterialRequest::whereIn('project_id', $projectIds)->where('status', 'disbursed')->count(),
        ];

        $projects = $user->projects;

        return view('livewire.inspector.dashboard', [
            'pendingTasks' => $pendingTasks,
            'pendingMaterials' => $pendingMaterials,
            'stats' => $stats,
            'projects' => $projects,
        ]);
    }
}
