<?php

namespace App\Livewire\Team;

use App\Models\User;
use App\Models\LeaveBalance;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showLeaveBalanceModal = false;
    public $editingUser = null;
    public $selectedUserForLeave = null;

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = 'contractor';

    // Leave balance form fields
    public $leaveBalanceForm = [
        'leave_type' => 'annual',
        'year' => null,
        'balance_days' => '',
    ];

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
            ->with(['leaveBalances', 'leaveRequests' => function ($query) {
                $query->selectRaw('user_id, COUNT(*) as total_requests, SUM(duration_days) as total_days')
                    ->groupBy('user_id');
            }])
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
            'currentLeaveBalance' => $this->getCurrentLeaveBalanceProperty(),
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

    public function resetCreateForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'contractor';
    }

    public function openLeaveBalanceModal($userId)
    {
        $this->selectedUserForLeave = User::findOrFail($userId);
        $this->leaveBalanceForm = [
            'leave_type' => 'vacation',
            'year' => now()->year,
            'balance_days' => '',
        ];
        $this->showLeaveBalanceModal = true;
    }

    public function closeLeaveBalanceModal()
    {
        $this->showLeaveBalanceModal = false;
        $this->selectedUserForLeave = null;
        $this->leaveBalanceForm = [
            'leave_type' => 'vacation',
            'year' => now()->year,
            'balance_days' => '',
        ];
    }

    public function saveLeaveBalance()
    {
        $this->validate([
            'leaveBalanceForm.leave_type' => 'required|string|in:vacation,sick,personal,bereavement,maternity,paternity',
            'leaveBalanceForm.year' => 'required|integer|min:2020|max:' . (now()->year + 2),
            'leaveBalanceForm.balance_days' => 'required|numeric|min:0|max:365',
        ]);

        LeaveBalance::updateOrCreate(
            [
                'user_id' => $this->selectedUserForLeave->id,
                'leave_type' => $this->leaveBalanceForm['leave_type'],
                'year' => $this->leaveBalanceForm['year'],
            ],
            [
                'balance_days' => $this->leaveBalanceForm['balance_days'],
            ]
        );

        Flux::toast('Leave balance updated successfully', variant: 'success');
        $this->closeLeaveBalanceModal();
    }

    public function getCurrentLeaveBalanceProperty()
    {
        if (!$this->selectedUserForLeave) {
            return null;
        }

        return LeaveBalance::where('user_id', $this->selectedUserForLeave->id)
            ->where('leave_type', $this->leaveBalanceForm['leave_type'])
            ->where('year', $this->leaveBalanceForm['year'])
            ->first();
    }
}
