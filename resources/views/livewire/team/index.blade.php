<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Team Members</flux:heading>
                @if(auth()->user()->isProjectManager())
                    <flux:button variant="primary" wire:click="openCreateModal" icon="plus">Add Team Member</flux:button>
                @endif
            </div>
        </div>
    </flux:header>

    <flux:main>
        {{-- Flash Messages --}}
        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg dark:bg-green-900/20 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filters --}}
        <flux:card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search team members..." icon="magnifying-glass" />
                
                <flux:select wire:model.live="roleFilter" placeholder="All Roles">
                    <flux:select.option value="">All Roles</flux:select.option>
                    <flux:select.option value="project_manager">Project Manager</flux:select.option>
                    <flux:select.option value="contractor">Contractor</flux:select.option>
                    <flux:select.option value="client">Client</flux:select.option>
                    <flux:select.option value="inspector">Inspector</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="sortBy">
                    <flux:select.option value="created_at">Recently Added</flux:select.option>
                    <flux:select.option value="name">Name</flux:select.option>
                    <flux:select.option value="email">Email</flux:select.option>
                </flux:select>
            </div>
        </flux:card>

        {{-- Team Members Table --}}
        <flux:card>
            @if($users->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Member</flux:table.column>
                        <flux:table.column>Role</flux:table.column>
                        <flux:table.column>Managing</flux:table.column>
                        <flux:table.column>Projects</flux:table.column>
                        <flux:table.column>Tasks</flux:table.column>
                        <flux:table.column>Leave Balance</flux:table.column>
                        <flux:table.column>Leave Used</flux:table.column>
                        <flux:table.column>Joined</flux:table.column>
                        @can('manageTeamMembers', auth()->user())
                            <flux:table.column>Actions</flux:table.column>
                        @endcan
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :src="null" :name="$user->name" />
                                        <div>
                                            <div class="font-medium flex items-center gap-2">
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <flux:badge color="lime" size="sm">You</flux:badge>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <flux:badge :color="match($user->role) {
                                        'project_manager' => 'blue',
                                        'contractor' => 'green',
                                        'client' => 'purple',
                                        'inspector' => 'orange',
                                        default => 'gray'
                                    }" size="sm">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <div class="text-center">
                                        <span class="font-semibold text-blue-600">{{ $user->managed_projects_count }}</span>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <div class="text-center">
                                        <span class="font-semibold text-green-600">{{ $user->projects_count }}</span>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <div class="text-center">
                                        <span class="font-semibold text-purple-600">{{ $user->tasks_count }}</span>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <div class="text-center">
                                        @php
                                            $totalLeaveBalance = $user->leaveBalances->sum('balance_days');
                                        @endphp
                                        <span class="font-semibold text-blue-600">{{ number_format($totalLeaveBalance, 1) }}</span>
                                        <div class="text-xs text-gray-500">days</div>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <div class="text-center">
                                        @php
                                            $totalLeaveUsed = $user->leaveRequests->first()?->total_days ?? 0;
                                            $totalLeaveRequests = $user->leaveRequests->first()?->total_requests ?? 0;
                                        @endphp
                                        <span class="font-semibold text-orange-600">{{ number_format($totalLeaveUsed, 1) }}</span>
                                        <div class="text-xs text-gray-500">{{ $totalLeaveRequests }} requests</div>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $user->created_at->diffForHumans() }}
                                    </div>
                                </flux:table.cell>
                                
                                @can('manageTeamMembers', auth()->user())
                                    <flux:table.cell>
                                        @if($user->id !== auth()->id())
                                            <div class="flex items-center gap-2">
                                                <flux:dropdown position="bottom end">
                                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal"></flux:button>
                                                    
                                                    <flux:menu>
                                                        <flux:menu.item wire:click="openEditModal({{ $user->id }})" icon="pencil">
                                                            Edit User
                                                        </flux:menu.item>
                                                        <flux:menu.item wire:click="openLeaveBalanceModal({{ $user->id }})" icon="calendar-days">
                                                            Manage Leave
                                                        </flux:menu.item>
                                                        <flux:menu.separator />
                                                        <flux:menu.item wire:click="deleteUser({{ $user->id }})" icon="trash" variant="danger">
                                                            Remove User
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </flux:table.cell>
                                @endif
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="text-center py-12">
                    <flux:icon.user-group class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No team members found</flux:heading>
                    <p class="text-gray-500 mb-4">Get started by adding your first team member</p>
                    @if(auth()->user()->isProjectManager())
                        <flux:button variant="primary" wire:click="openCreateModal" icon="plus">Add Team Member</flux:button>
                    @endif
                </div>
            @endif
        </flux:card>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    </flux:main>

    {{-- Create Modal --}}
    <flux:modal name="create-user" wire:model.self="showCreateModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Team Member</flux:heading>
                <flux:text class="mt-2">Add a new member to your team.</flux:text>
            </div>

            <form wire:submit="createUser" class="space-y-6">
                <flux:input wire:model="name" label="Full Name" placeholder="e.g., John Doe" required />
                
                <flux:input wire:model="email" type="email" label="Email Address" placeholder="e.g., john@example.com" required />
                
                <flux:select wire:model="role" label="Role" required>
                    <flux:select.option value="project_manager">Project Manager</flux:select.option>
                    <flux:select.option value="contractor">Contractor</flux:select.option>
                    <flux:select.option value="client">Client</flux:select.option>
                    <flux:select.option value="inspector">Inspector</flux:select.option>
                </flux:select>
                
                <flux:input wire:model="password" type="password" label="Password" placeholder="••••••••" required />
                
                <flux:input wire:model="password_confirmation" type="password" label="Confirm Password" placeholder="••••••••" required />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Add Member</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal name="edit-user" wire:model.self="showEditModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Team Member</flux:heading>
                <flux:text class="mt-2">Update team member details.</flux:text>
            </div>

            <form wire:submit="updateUser" class="space-y-6">
                <flux:input wire:model="name" label="Full Name" placeholder="e.g., John Doe" required />
                
                <flux:input wire:model="email" type="email" label="Email Address" placeholder="e.g., john@example.com" required />
                
                <flux:select wire:model="role" label="Role" required>
                    <flux:select.option value="project_manager">Project Manager</flux:select.option>
                    <flux:select.option value="contractor">Contractor</flux:select.option>
                    <flux:select.option value="client">Client</flux:select.option>
                    <flux:select.option value="inspector">Inspector</flux:select.option>
                </flux:select>

                <flux:separator />

                <div class="space-y-4">
                    <flux:text class="text-sm">Leave password fields empty to keep the current password</flux:text>
                    
                    <flux:input wire:model="password" type="password" label="New Password" placeholder="••••••••" />
                    
                    <flux:input wire:model="password_confirmation" type="password" label="Confirm New Password" placeholder="••••••••" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Update Member</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Leave Balance Management Modal --}}
    <flux:modal wire:model="showLeaveBalanceModal" width="2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Manage Leave Balance</flux:heading>
                <flux:text class="mt-2">Set leave days for team members.</flux:text>
            </div>
            
            <form wire:submit="saveLeaveBalance" class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center gap-3">
                        <flux:avatar size="sm" :name="$selectedUserForLeave?->name" />
                        <div>
                            <div class="font-medium">{{ $selectedUserForLeave?->name }}</div>
                            <div class="text-sm text-gray-500">{{ $selectedUserForLeave?->email }}</div>
                        </div>
                    </div>
                </div>
                
                <flux:select wire:model="leaveBalanceForm.leave_type" label="Leave Type" required>
                    <flux:select.option value="vacation">Vacation Leave</flux:select.option>
                    <flux:select.option value="sick">Sick Leave</flux:select.option>
                    <flux:select.option value="personal">Personal Leave</flux:select.option>
                    <flux:select.option value="bereavement">Bereavement Leave</flux:select.option>
                    <flux:select.option value="maternity">Maternity Leave</flux:select.option>
                    <flux:select.option value="paternity">Paternity Leave</flux:select.option>
                </flux:select>
                
                <flux:select wire:model="leaveBalanceForm.year" label="Year" required>
                    @for($year = now()->year; $year <= now()->year + 1; $year++)
                        <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                    @endfor
                </flux:select>
                
                <flux:input 
                    wire:model="leaveBalanceForm.balance_days" 
                    type="number" 
                    step="0.5" 
                    min="0" 
                    max="365"
                    label="Leave Days to Add"
                    placeholder="Enter number of days"
                    required />
                
                @if($currentLeaveBalance)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="text-sm">
                            <span class="font-medium text-blue-800">Current Balance:</span>
                            <span class="text-blue-600 ml-2">{{ number_format($currentLeaveBalance->balance_days, 1) }} days</span>
                        </div>
                    </div>
                @endif

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Save Leave Balance</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
