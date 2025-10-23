<?php

namespace App\Livewire\Settings;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Component;

class RolesAndPermissions extends Component
{
    public $showRoleModal = false;
    public $showPermissionModal = false;
    public $editingRole = null;
    
    // Role fields
    public $role_name = '';
    public $role_permissions = [];
    
    // Selected role for viewing
    public $selectedRole = null;

    public function mount()
    {
        // Only super admins can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Unauthorized access');
    }

    public function openRoleModal()
    {
        $this->resetRoleForm();
        $this->showRoleModal = true;
    }

    public function openEditRoleModal($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        
        $this->editingRole = $role->id;
        $this->role_name = $role->name;
        $this->role_permissions = $role->permissions->pluck('id')->toArray();
        $this->showRoleModal = true;
    }

    public function saveRole()
    {
        $this->validate([
            'role_name' => 'required|string|max:255|unique:roles,name,' . ($this->editingRole ?? 'NULL'),
            'role_permissions' => 'array',
        ]);

        if ($this->editingRole) {
            $role = Role::findOrFail($this->editingRole);
            $role->update(['name' => $this->role_name]);
        } else {
            $role = Role::create([
                'name' => $this->role_name,
                'guard_name' => 'web',
            ]);
        }

        // Sync permissions
        $role->syncPermissions($this->role_permissions);

        $this->showRoleModal = false;
        $this->resetRoleForm();
    }

    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        
        // Prevent deletion of critical roles
        if (in_array($role->name, ['super_admin', 'project_manager'])) {
            session()->flash('error', 'Cannot delete critical system roles.');
            return;
        }

        $role->delete();
    }

    public function selectRole($roleId)
    {
        $this->selectedRole = $roleId;
    }

    public function togglePermission($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
        } else {
            $role->givePermissionTo($permission);
        }

        // Refresh selected role if it's the one being edited
        if ($this->selectedRole == $roleId) {
            $this->selectedRole = $roleId;
        }
    }

    private function resetRoleForm()
    {
        $this->editingRole = null;
        $this->role_name = '';
        $this->role_permissions = [];
    }

    public function render()
    {
        $roles = Role::with('permissions')->withCount('users')->get();
        $permissions = Permission::orderBy('name')->get();
        
        // Group permissions by category
        $groupedPermissions = $permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return ucfirst($parts[count($parts) - 1] ?? 'General');
        });

        $selectedRoleData = $this->selectedRole 
            ? Role::with('permissions', 'users')->findOrFail($this->selectedRole)
            : null;

        return view('livewire.settings.roles-and-permissions', [
            'roles' => $roles,
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
            'selectedRoleData' => $selectedRoleData,
        ]);
    }
}
