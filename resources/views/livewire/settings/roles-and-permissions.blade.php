<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">

        <div class="flex items-center justify-between">
            <div class="w-full">
                <flux:heading size="lg">Roles & Permissions Management</flux:heading>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage system roles and their permissions</p>
            </div>
            <flux:button variant="primary" wire:click="openRoleModal" icon="plus">
                Create Role
            </flux:button>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Roles</div>
                <div class="text-3xl font-bold">{{ $roles->count() }}</div>
            </flux:card>
            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Permissions</div>
                <div class="text-3xl font-bold">{{ $permissions->count() }}</div>
            </flux:card>
            <flux:card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Permission Categories</div>
                <div class="text-3xl font-bold">{{ $groupedPermissions->count() }}</div>
            </flux:card>
        </div>
        </div>
    </flux:header>

    <flux:main>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Roles List --}}
            <div class="lg:col-span-1">
                <flux:card>
                    <flux:heading size="md" class="mb-4">Roles</flux:heading>
                    <div class="space-y-2">
                        @foreach($roles as $role)
                            <div 
                                class="p-3 rounded-lg border cursor-pointer transition-all {{ $selectedRole == $role->id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                                wire:click="selectRole({{ $role->id }})"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                    <flux:badge color="gray" size="sm">{{ $role->users_count }} users</flux:badge>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $role->permissions->count() }} permissions
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            </div>

            {{-- Role Details & Permissions --}}
            <div class="lg:col-span-2">
                @if($selectedRoleData)
                    <flux:card>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <flux:heading size="md">{{ ucfirst(str_replace('_', ' ', $selectedRoleData->name)) }}</flux:heading>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $selectedRoleData->permissions->count() }} permissions • {{ $selectedRoleData->users->count() }} users
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="openEditRoleModal({{ $selectedRoleData->id }})" icon="pencil">
                                    Edit
                                </flux:button>
                                @if(!in_array($selectedRoleData->name, ['super_admin', 'project_manager']))
                                    <flux:button 
                                        size="sm" 
                                        variant="danger" 
                                        wire:click="deleteRole({{ $selectedRoleData->id }})"
                                        wire:confirm="Delete this role? Users with this role will lose their permissions."
                                        icon="trash"
                                    >
                                        Delete
                                    </flux:button>
                                @endif
                            </div>
                        </div>

                        {{-- Permissions by Category --}}
                        <div class="space-y-6">
                            @foreach($groupedPermissions as $category => $categoryPermissions)
                                <div>
                                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                        <flux:icon.shield-check class="w-4 h-4" />
                                        {{ $category }} Permissions
                                        <span class="text-xs font-normal text-gray-500">
                                            ({{ $categoryPermissions->whereIn('id', $selectedRoleData->permissions->pluck('id'))->count() }}/{{ $categoryPermissions->count() }})
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($categoryPermissions as $permission)
                                            <label class="flex items-center gap-3 p-2 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-all">
                                                <input 
                                                    type="checkbox" 
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    wire:click="togglePermission({{ $selectedRoleData->id }}, {{ $permission->id }})"
                                                    {{ $selectedRoleData->permissions->contains('id', $permission->id) ? 'checked' : '' }}
                                                >
                                                <span class="text-sm flex-1">{{ ucfirst($permission->name) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Users with this Role --}}
                        @if($selectedRoleData->users->count() > 0)
                            <div class="mt-6 pt-6 border-t dark:border-gray-700">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    Users with {{ ucfirst(str_replace('_', ' ', $selectedRoleData->name)) }} role
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedRoleData->users as $user)
                                        <div class="px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-full text-sm">
                                            {{ $user->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @else
                    <flux:card class="text-center py-12">
                        <flux:icon.shield-check class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">Select a Role</flux:heading>
                        <p class="text-gray-500">Click on a role from the list to view and manage its permissions</p>
                    </flux:card>
                @endif
            </div>
        </div>

        {{-- All Permissions Overview --}}
        <flux:card class="mt-6">
            <flux:heading size="md" class="mb-4">All Permissions Overview</flux:heading>
            <div class="space-y-4">
                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            {{ $category }} ({{ $categoryPermissions->count() }})
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($categoryPermissions as $permission)
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </flux:main>

    {{-- Create/Edit Role Modal --}}
    <flux:modal name="role-modal" wire:model.self="showRoleModal" class="md:w-[700px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingRole ? 'Edit' : 'Create' }} Role</flux:heading>
                <flux:text class="mt-2">{{ $editingRole ? 'Update role details and permissions' : 'Create a new role and assign permissions' }}</flux:text>
            </div>

            <form wire:submit="saveRole" class="space-y-6">
                <flux:input 
                    wire:model="role_name" 
                    label="Role Name" 
                    placeholder="e.g., site_supervisor" 
                    required 
                />

                <div>
                    <label class="block text-sm font-medium mb-3">Assign Permissions</label>
                    <div class="max-h-96 overflow-y-auto space-y-4 border dark:border-gray-700 rounded-lg p-4">
                        @foreach($groupedPermissions as $category => $categoryPermissions)
                            <div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $category }}
                                </div>
                                <div class="space-y-2 ml-4">
                                    @foreach($categoryPermissions as $permission)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                wire:model="role_permissions"
                                                value="{{ $permission->id }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm">{{ ucfirst($permission->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Selected: {{ count($role_permissions) }} permissions</p>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        {{ $editingRole ? 'Update' : 'Create' }} Role
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
