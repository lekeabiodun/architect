<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingProject = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $client_name = '';
    public $location = '';
    public $status = 'active';
    public $planned_start_date = '';
    public $planned_end_date = '';
    public $estimated_budget = '';
    public $manager_id = '';
    
    // Filters
    public $search = '';
    public $statusFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->manager_id = auth()->id();
    }

    public function render()
    {
        $projects = Project::query()
            ->with(['manager', 'phases'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('client_name', 'like', '%' . $this->search . '%')
                      ->orWhere('location', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);

        $managers = User::whereIn('role', ['project_manager', 'contractor'])->get();

        return view('livewire.project.index', [
            'projects' => $projects,
            'managers' => $managers,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($projectId)
    {
        $project = Project::findOrFail($projectId);
        
        $this->editingProject = $project->id;
        $this->name = $project->name;
        $this->description = $project->description ?? '';
        $this->client_name = $project->client_name;
        $this->location = $project->location ?? '';
        $this->status = $project->status;
        $this->planned_start_date = $project->planned_start_date?->format('Y-m-d') ?? '';
        $this->planned_end_date = $project->planned_end_date?->format('Y-m-d') ?? '';
        $this->estimated_budget = $project->estimated_budget ?? '';
        $this->manager_id = $project->manager_id ?? auth()->id();
        
        $this->showEditModal = true;
    }

    public function createProject()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'manager_id' => 'required|exists:users,id',
        ]);

        Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'client_name' => $this->client_name,
            'location' => $this->location,
            'status' => $this->status,
            'planned_start_date' => $this->planned_start_date ?: null,
            'planned_end_date' => $this->planned_end_date ?: null,
            'estimated_budget' => $this->estimated_budget ?: null,
            'manager_id' => $this->manager_id,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        
        session()->flash('message', 'Project created successfully!');
    }

    public function updateProject()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'manager_id' => 'required|exists:users,id',
        ]);

        $project = Project::findOrFail($this->editingProject);
        
        $project->update([
            'name' => $this->name,
            'description' => $this->description,
            'client_name' => $this->client_name,
            'location' => $this->location,
            'status' => $this->status,
            'planned_start_date' => $this->planned_start_date ?: null,
            'planned_end_date' => $this->planned_end_date ?: null,
            'estimated_budget' => $this->estimated_budget ?: null,
            'manager_id' => $this->manager_id,
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        
        session()->flash('message', 'Project updated successfully!');
    }

    public function deleteProject($projectId)
    {
        $project = Project::findOrFail($projectId);
        $project->delete();
        
        session()->flash('message', 'Project deleted successfully!');
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    private function resetForm()
    {
        $this->editingProject = null;
        $this->name = '';
        $this->description = '';
        $this->client_name = '';
        $this->location = '';
        $this->status = 'active';
        $this->planned_start_date = '';
        $this->planned_end_date = '';
        $this->estimated_budget = '';
        $this->manager_id = auth()->id();
        $this->resetErrorBag();
    }
}
