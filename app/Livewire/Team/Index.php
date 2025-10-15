<?php

namespace App\Livewire\Team;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingUser = null;
    
    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = 'contractor';
    
    // Filters
    public $search = '';
    public $roleFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
    ];

    public function render()
    {
        $users = User::query()
            ->withCount(['managedProjects', 'tasks', 'projects'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);

        return view('livewire.team.index', [
            'users' => $users,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        
        $this->editingUser = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        $this->password_confirmation = '';
        
        $this->showEditModal = true;
    }

    public function createUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:project_manager,contractor,client,inspector',
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'role' => $this->role,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        
        session()->flash('message', 'Team member added successfully!');
    }

    public function updateUser()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUser,
            'role' => 'required|in:project_manager,contractor,client,inspector',
        ];

        if ($this->password) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules);

        $user = User::findOrFail($this->editingUser);
        
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        $user->update($data);

        $this->showEditModal = false;
        $this->resetForm();
        
        session()->flash('message', 'Team member updated successfully!');
    }

    public function deleteUser($userId)
    {
        // Prevent deleting the current user
        if ($userId == auth()->id()) {
            session()->flash('error', 'You cannot delete your own account!');
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();
        
        session()->flash('message', 'Team member removed successfully!');
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
        $this->editingUser = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'contractor';
        $this->resetErrorBag();
    }
}
