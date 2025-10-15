<?php

namespace App\Livewire\Project;

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public $project;
    public $showPhaseModal = false;
    public $showTaskModal = false;
    public $editingPhase = null;
    public $editingTask = null;
    public $selectedPhase = null;
    
    // Phase form fields
    public $phase_name = '';
    public $phase_description = '';
    public $phase_order = 0;
    public $phase_weight = 0;
    public $phase_planned_start_date = '';
    public $phase_planned_end_date = '';
    
    // Task form fields
    public $task_name = '';
    public $task_description = '';
    public $task_order = 0;
    public $task_weight = 0;
    public $task_planned_start_date = '';
    public $task_planned_end_date = '';
    public $task_estimated_cost = '';
    public $task_estimated_hours = '';
    public $task_assigned_to = '';
    public $task_predecessor_id = '';

    public function mount($id)
    {
        $this->project = Project::with(['phases.tasks.assignedUser', 'manager'])->findOrFail($id);
    }

    public function render()
    {
        // Reload project to get fresh data
        $this->project->load(['phases.tasks.assignedUser', 'manager']);
        
        $users = User::all();
        $availableTasks = collect();
        
        if ($this->selectedPhase) {
            $availableTasks = Task::where('phase_id', $this->selectedPhase)->get();
        }

        return view('livewire.project.show', [
            'users' => $users,
            'availableTasks' => $availableTasks,
        ]);
    }

    // Phase Management
    public function openPhaseModal()
    {
        $this->resetPhaseForm();
        $this->phase_order = $this->project->phases->count() + 1;
        $this->showPhaseModal = true;
    }

    public function openEditPhaseModal($phaseId)
    {
        $phase = Phase::findOrFail($phaseId);
        
        $this->editingPhase = $phase->id;
        $this->phase_name = $phase->name;
        $this->phase_description = $phase->description ?? '';
        $this->phase_order = $phase->order;
        $this->phase_weight = $phase->weight;
        $this->phase_planned_start_date = $phase->planned_start_date?->format('Y-m-d') ?? '';
        $this->phase_planned_end_date = $phase->planned_end_date?->format('Y-m-d') ?? '';
        
        $this->showPhaseModal = true;
    }

    public function savePhase()
    {
        $this->validate([
            'phase_name' => 'required|string|max:255',
            'phase_description' => 'nullable|string',
            'phase_order' => 'required|integer|min:0',
            'phase_weight' => 'required|numeric|min:0|max:100',
            'phase_planned_start_date' => 'nullable|date',
            'phase_planned_end_date' => 'nullable|date|after_or_equal:phase_planned_start_date',
        ]);

        if ($this->editingPhase) {
            $phase = Phase::findOrFail($this->editingPhase);
            $phase->update([
                'name' => $this->phase_name,
                'description' => $this->phase_description,
                'order' => $this->phase_order,
                'weight' => $this->phase_weight,
                'planned_start_date' => $this->phase_planned_start_date ?: null,
                'planned_end_date' => $this->phase_planned_end_date ?: null,
            ]);
        } else {
            Phase::create([
                'project_id' => $this->project->id,
                'name' => $this->phase_name,
                'description' => $this->phase_description,
                'order' => $this->phase_order,
                'weight' => $this->phase_weight,
                'planned_start_date' => $this->phase_planned_start_date ?: null,
                'planned_end_date' => $this->phase_planned_end_date ?: null,
            ]);
        }

        $this->showPhaseModal = false;
        $this->resetPhaseForm();
        $this->project->refresh();
    }

    public function deletePhase($phaseId)
    {
        $phase = Phase::findOrFail($phaseId);
        $phase->delete();
        $this->project->refresh();
    }

    // Task Management
    public function openTaskModal($phaseId)
    {
        $this->resetTaskForm();
        $this->selectedPhase = $phaseId;
        $phase = Phase::findOrFail($phaseId);
        $this->task_order = $phase->tasks->count() + 1;
        $this->showTaskModal = true;
    }

    public function openEditTaskModal($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        $this->editingTask = $task->id;
        $this->selectedPhase = $task->phase_id;
        $this->task_name = $task->name;
        $this->task_description = $task->description ?? '';
        $this->task_order = $task->order;
        $this->task_weight = $task->weight;
        $this->task_planned_start_date = $task->planned_start_date?->format('Y-m-d') ?? '';
        $this->task_planned_end_date = $task->planned_end_date?->format('Y-m-d') ?? '';
        $this->task_estimated_cost = $task->estimated_cost ?? '';
        $this->task_estimated_hours = $task->estimated_hours ?? '';
        $this->task_assigned_to = $task->assigned_to ?? '';
        $this->task_predecessor_id = $task->predecessor_task_id ?? '';
        
        $this->showTaskModal = true;
    }

    public function saveTask()
    {
        $this->validate([
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'task_order' => 'required|integer|min:0',
            'task_weight' => 'required|numeric|min:0|max:100',
            'task_planned_start_date' => 'nullable|date',
            'task_planned_end_date' => 'nullable|date|after_or_equal:task_planned_start_date',
            'task_estimated_cost' => 'nullable|numeric|min:0',
            'task_estimated_hours' => 'nullable|numeric|min:0',
            'task_assigned_to' => 'nullable|exists:users,id',
            'task_predecessor_id' => 'nullable|exists:tasks,id',
        ]);

        $data = [
            'phase_id' => $this->selectedPhase,
            'name' => $this->task_name,
            'description' => $this->task_description,
            'order' => $this->task_order,
            'weight' => $this->task_weight,
            'planned_start_date' => $this->task_planned_start_date ?: null,
            'planned_end_date' => $this->task_planned_end_date ?: null,
            'estimated_cost' => $this->task_estimated_cost ?: null,
            'estimated_hours' => $this->task_estimated_hours ?: null,
            'assigned_to' => $this->task_assigned_to ?: null,
            'predecessor_task_id' => $this->task_predecessor_id ?: null,
        ];

        if ($this->editingTask) {
            $task = Task::findOrFail($this->editingTask);
            $task->update($data);
        } else {
            Task::create($data);
        }

        $this->showTaskModal = false;
        $this->resetTaskForm();
        $this->project->refresh();
    }

    public function toggleTaskStatus($taskId)
    {
        $task = Task::findOrFail($taskId);
        $task->toggleStatus();
        $this->project->refresh();
    }

    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        $task->delete();
        $this->project->refresh();
    }

    private function resetPhaseForm()
    {
        $this->editingPhase = null;
        $this->phase_name = '';
        $this->phase_description = '';
        $this->phase_order = 0;
        $this->phase_weight = 0;
        $this->phase_planned_start_date = '';
        $this->phase_planned_end_date = '';
        $this->resetErrorBag();
    }

    private function resetTaskForm()
    {
        $this->editingTask = null;
        $this->task_name = '';
        $this->task_description = '';
        $this->task_order = 0;
        $this->task_weight = 0;
        $this->task_planned_start_date = '';
        $this->task_planned_end_date = '';
        $this->task_estimated_cost = '';
        $this->task_estimated_hours = '';
        $this->task_assigned_to = '';
        $this->task_predecessor_id = '';
        $this->resetErrorBag();
    }
}
