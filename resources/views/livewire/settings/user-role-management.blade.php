<div>
    {{-- Do your work, then step back. --}}
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">User Role Management</flux:heading>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Assign roles to users and manage their permissions</p>
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex gap-4">
                <flux:input wire:model.live="search" placeholder="Search users..." icon="magnifying-glass" class="flex-1" />
                <flux:select wire:model.live="role_filter" placeholder="All Roles" class="w-48">
                    <flux:select.option value="">All Roles</flux:select.option>
                    @foreach($roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:header>

    <flux:main>
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Users List --}}
            <div class="lg:col-span-1">
                <flux:card>
                    <flux:heading size="md" class="mb-4">Users ({{ $users->total() }})</flux:heading>
                    <div class="space-y-2 max-h-[600px] overflow-y-auto">
                        @forelse($users as $user)
                            <div 
                                class="p-3 rounded-lg border cursor-pointer transition-all {{ $selectedUser == $user->id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                                wire:click="selectUser({{ $user->id }})"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold">{{ $user->name }}</span>
                                    @if($user->id === auth()->id())
                                        <flux:badge color="blue" size="sm">You</flux:badge>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ $user->email }}</div>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs">
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500">No roles</span>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8">No users found</p>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </flux:card>
            </div>

            {{-- User Details & Role Assignment --}}
            <div class="lg:col-span-2">
                @if($selectedUserData)
                    <flux:card>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <flux:heading size="md">{{ $selectedUserData->name }}</flux:heading>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $selectedUserData->email }}
                                    @if($selectedUserData->id === auth()->id())
                                        <flux:badge color="blue" size="sm" class="ml-2">Current User</flux:badge>
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Assign Roles --}}
                        <div class="mb-6">
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assign Roles</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($roles as $role)
                                    @php
                                        $hasRole = in_array($role->id, $user_roles);
                                        $isSuperAdminSelf = $selectedUserData->id === auth()->id() && $role->name === 'super_admin';
                                    @endphp
                                    <label class="flex items-center gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-all {{ $hasRole ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-500' : '' }}">
                                        <input 
                                            type="checkbox" 
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                            wire:model="user_roles"
                                            value="{{ $role->id }}"
                                            {{ $isSuperAdminSelf ? 'disabled' : '' }}
                                        >
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $role->permissions->count() }} permissions</div>
                                        </div>
                                        @if($isSuperAdminSelf)
                                            <flux:badge color="blue" size="sm">Protected</flux:badge>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-4">
                                <flux:button variant="primary" wire:click="updateUserRoles">
                                    Update User Roles
                                </flux:button>
                            </div>
                        </div>

                        {{-- Current Roles & Permissions --}}
                        @if($selectedUserData->roles->count() > 0)
                            <div class="pt-6 border-t dark:border-gray-700">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Roles & Permissions</div>
                                <div class="space-y-4">
                                    @foreach($selectedUserData->roles as $role)
                                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                                @if(!($selectedUserData->id === auth()->id() && $role->name === 'super_admin'))
                                                    <flux:button 
                                                        size="sm" 
                                                        variant="danger"
                                                        wire:click="removeRole({{ $selectedUserData->id }}, {{ $role->id }})"
                                                        wire:confirm="Remove {{ $role->name }} role from this user?"
                                                    >
                                                        Remove Role
                                                    </flux:button>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                                {{ $role->permissions->count() }} permissions
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($role->permissions->take(10) as $permission)
                                                    <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                                @if($role->permissions->count() > 10)
                                                    <span class="px-2 py-0.5 bg-gray-300 dark:bg-gray-600 rounded text-xs font-medium">
                                                        +{{ $role->permissions->count() - 10 }} more
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- User Projects --}}
                        @if($selectedUserData->projects->count() > 0)
                            <div class="mt-6 pt-6 border-t dark:border-gray-700">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    Assigned Projects ({{ $selectedUserData->projects->count() }})
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($selectedUserData->projects as $project)
                                        <div class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded text-sm">
                                            {{ $project->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @else
                    <flux:card class="text-center py-12">
                        <flux:icon.user-group class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">Select a User</flux:heading>
                        <p class="text-gray-500">Click on a user from the list to manage their roles</p>
                    </flux:card>
                @endif
            </div>
        </div>

        {{-- Role Summary Table --}}
        <flux:card class="mt-6">
            <flux:heading size="md" class="mb-4">Role Distribution</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Role</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold">Users</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold">Permissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <flux:badge color="gray">{{ $role->users()->count() }}</flux:badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    {{ $role->permissions->count() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    </flux:main>
</div>
