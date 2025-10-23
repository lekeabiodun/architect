<?php

namespace App\Livewire\Project;

use App\Models\User;
use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingProject = null;

    // Form fields
    public $name = '';
    public $description = '';
    public $client_id = '';
    public $location = '';
    public $status = 'active';
    public $planned_start_date = '';
    public $planned_end_date = '';
    public $estimated_budget = '';
    public $manager_id = '';
    public $inspector_id = '';
    public $currency = 'USD';
    public $team_members = [];

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
        $this->manager_id = Auth::id();
    }

    public function render()
    {
        $user = Auth::user();

        $projects = Project::query()
            ->with(['manager', 'client', 'inspector', 'phases'])
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                // Non-admins see only projects they're assigned to or manage
                $query->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id)
                        ->orWhereHas('users', function ($uq) use ($user) {
                            $uq->where('user_id', $user->id);
                        });
                });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('location', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);

        $managers = User::role(['project_manager', 'contractor'])->get();
        $inspectors = User::role('inspector')->get();
        $clients = User::role('client')->get();
        $allUsers = User::orderBy('name')->get();

        return view('livewire.project.index', [
            'projects' => $projects,
            'managers' => $managers,
            'inspectors' => $inspectors,
            'clients' => $clients,
            'allUsers' => $allUsers,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($projectId)
    {
        $project = Project::with('users')->findOrFail($projectId);
        $this->authorize('update', $project);

        $this->editingProject = $project->id;
        $this->name = $project->name;
        $this->description = $project->description ?? '';
        $this->client_id = $project->client_id ?? '';
        $this->location = $project->location ?? '';
        $this->status = $project->status;
        $this->planned_start_date = $project->planned_start_date?->format('Y-m-d') ?? '';
        $this->planned_end_date = $project->planned_end_date?->format('Y-m-d') ?? '';
        $this->estimated_budget = $project->estimated_budget ?? '';
        $this->manager_id = $project->manager_id ?? Auth::id();
        $this->inspector_id = $project->inspector_id ?? '';
        $this->currency = $project->currency ?? 'USD';
        $this->team_members = $project->users->pluck('id')->toArray();

        $this->showEditModal = true;
    }

    public function createProject()
    {
        $this->authorize('create', Project::class);

        $this->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'manager_id' => 'required|exists:users,id',
            'inspector_id' => 'nullable|exists:users,id',
            'currency' => 'required|in:USD,NGN',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
        ]);

        $project = Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'client_id' => $this->client_id ?: null,
            'location' => $this->location,
            'status' => $this->status,
            'planned_start_date' => $this->planned_start_date ?: null,
            'planned_end_date' => $this->planned_end_date ?: null,
            'estimated_budget' => $this->estimated_budget ?: null,
            'manager_id' => $this->manager_id,
            'inspector_id' => $this->inspector_id ?: null,
            'currency' => $this->currency,
        ]);

        // Attach team members with their system roles
        if (!empty($this->team_members)) {
            foreach ($this->team_members as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $project->users()->attach($userId, ['role' => $user->getRoleNames()->first()]);
                }
            }
        }

        $this->showCreateModal = false;
        $this->resetForm();

        session()->flash('message', 'Project created successfully!');
    }

    public function updateProject()
    {
        $project = Project::findOrFail($this->editingProject);
        $this->authorize('update', $project);

        $this->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'manager_id' => 'required|exists:users,id',
            'inspector_id' => 'nullable|exists:users,id',
            'currency' => 'required|in:USD,NGN',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
        ]);

        $project->update([
            'name' => $this->name,
            'description' => $this->description,
            'client_id' => $this->client_id ?: null,
            'location' => $this->location,
            'status' => $this->status,
            'planned_start_date' => $this->planned_start_date ?: null,
            'planned_end_date' => $this->planned_end_date ?: null,
            'estimated_budget' => $this->estimated_budget ?: null,
            'manager_id' => $this->manager_id,
            'inspector_id' => $this->inspector_id ?: null,
            'currency' => $this->currency,
        ]);

        // Sync team members with their system roles
        if (!empty($this->team_members)) {
            $syncData = [];
            foreach ($this->team_members as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $syncData[$userId] = ['role' => $user->getRoleNames()->first()];
                }
            }
            $project->users()->sync($syncData);
        } else {
            $project->users()->detach();
        }

        $this->showEditModal = false;
        $this->resetForm();

        session()->flash('message', 'Project updated successfully!');
    }

    public function deleteProject($projectId)
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('delete', $project);

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
        $this->client_id = '';
        $this->location = '';
        $this->status = 'active';
        $this->planned_start_date = '';
        $this->planned_end_date = '';
        $this->estimated_budget = '';
        $this->manager_id = Auth::id();
        $this->inspector_id = '';
        $this->currency = 'USD';
        $this->team_members = [];
        $this->resetErrorBag();
    }
}
