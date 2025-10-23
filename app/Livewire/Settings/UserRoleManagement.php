<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class UserRoleManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $role_filter = '';
    public $selectedUser = null;
    public $user_roles = [];

    protected $queryString = ['search', 'role_filter'];

    public function mount()
    {
        // Only super admins can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Unauthorized access');
    }

    public function selectUser($userId)
    {
        $user = User::with('roles')->findOrFail($userId);
        $this->selectedUser = $user->id;
        $this->user_roles = $user->roles->pluck('id')->toArray();
    }

    public function updateUserRoles()
    {
        if (!$this->selectedUser) {
            return;
        }

        $user = User::findOrFail($this->selectedUser);
        
        // Prevent removing super_admin from yourself
        if ($user->id === auth()->id()) {
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole && !in_array($superAdminRole->id, $this->user_roles)) {
                session()->flash('error', 'You cannot remove super admin role from yourself.');
                return;
            }
        }

        $user->syncRoles($this->user_roles);
        
        session()->flash('success', 'User roles updated successfully.');
    }

    public function assignRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        $user->assignRole($role);
        
        if ($this->selectedUser == $userId) {
            $this->user_roles = $user->roles()->pluck('roles.id')->toArray();
        }
    }

    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        // Prevent removing super_admin from yourself
        if ($user->id === auth()->id() && $role->name === 'super_admin') {
            session()->flash('error', 'You cannot remove super admin role from yourself.');
            return;
        }
        
        $user->removeRole($role);
        
        if ($this->selectedUser == $userId) {
            $this->user_roles = $user->roles()->pluck('roles.id')->toArray();
        }
    }

    public function render()
    {
        $query = User::query()->with('roles');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->role_filter) {
            $query->whereHas('roles', function($q) {
                $q->where('roles.id', $this->role_filter);
            });
        }

        $users = $query->orderBy('name')->paginate(15);
        $roles = Role::orderBy('name')->get();
        
        $selectedUserData = $this->selectedUser 
            ? User::with('roles', 'projects')->findOrFail($this->selectedUser)
            : null;

        return view('livewire.settings.user-role-management', [
            'users' => $users,
            'roles' => $roles,
            'selectedUserData' => $selectedUserData,
        ]);
    }
}
