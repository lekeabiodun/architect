<div>    
    <flux:header class="flex items-center justify-between">
        <flux:heading size="lg">Projects</flux:heading>
        <flux:button variant="primary" wire:click="openCreateModal" icon="plus">New Project</flux:button>
    </flux:header>

    <flux:main>
        {{-- Flash Message --}}
        @if (session()->has('message'))
            <div class="mb-4">
                {{ session('message') }}
            </div>
        @endif

        {{-- Filters --}}
        <flux:card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search projects..." icon="magnifying-glass" />
                
                <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="on_hold">On Hold</flux:select.option>
                    <flux:select.option value="completed">Completed</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="sortBy">
                    <flux:select.option value="created_at">Recently Added</flux:select.option>
                    <flux:select.option value="name">Name</flux:select.option>
                    <flux:select.option value="overall_progress">Progress</flux:select.option>
                    <flux:select.option value="planned_end_date">End Date</flux:select.option>
                </flux:select>
            </div>
        </flux:card>

        {{-- Projects Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @forelse($projects as $project)
                <flux:card class="hover:shadow-lg transition-shadow">
                    <div class="space-y-4">
                        {{-- Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <a href="{{ route('projects.show', $project->id) }}" class="text-lg font-semibold text-blue-600 hover:underline">
                                    {{ $project->name }}
                                </a>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->client_name }}</p>
                            </div>
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" icon-variant="mini" />
                                
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEditModal({{ $project->id }})">Edit</flux:menu.item>
                                    <flux:menu.item icon="trash" variant="danger" wire:click="deleteProject({{ $project->id }})" wire:confirm="Are you sure you want to delete this project?">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        {{-- Description --}}
                        @if($project->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ $project->description }}</p>
                        @endif

                        {{-- Location --}}
                        @if($project->location)
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <flux:icon.map-pin class="w-4 h-4" />
                                <span>{{ $project->location }}</span>
                            </div>
                        @endif

                        {{-- Progress --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium">Progress</span>
                                <span class="text-sm font-semibold">{{ number_format($project->overall_progress, 0) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $project->overall_progress }}%"></div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between pt-4 border-t">
                            <flux:badge :color="match($project->status) {
                                'active' => 'green',
                                'on_hold' => 'yellow',
                                'completed' => 'blue',
                                'cancelled' => 'red',
                                default => 'gray'
                            }">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </flux:badge>
                            
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <flux:icon.bars-3 class="w-4 h-4" />
                                <span>{{ $project->phases->count() }} phases</span>
                            </div>
                        </div>

                        {{-- Manager --}}
                        @if($project->manager)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:avatar size="xs" :src="null" :name="$project->manager->name" />
                                <span class="text-gray-600 dark:text-gray-300">{{ $project->manager->name }}</span>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @empty
                <div class="col-span-full">
                    <flux:card class="text-center py-12">
                        <flux:icon.folder-open class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">No projects found</flux:heading>
                        <p class="text-gray-500 mb-4">Get started by creating your first project</p>
                        <flux:button variant="primary" wire:click="openCreateModal" icon="plus">Create Project</flux:button>
                    </flux:card>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    </flux:main>

    {{-- Create Modal --}}
    <flux:modal name="create-project" wire:model.self="showCreateModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Create Project</flux:heading>
                <flux:text class="mt-2">Start a new construction project.</flux:text>
            </div>

            <form wire:submit="createProject" class="space-y-6">
                <flux:input wire:model="name" label="Project Name" placeholder="e.g., Luxury Villa Construction" required />
                
                <flux:textarea wire:model="description" label="Description" placeholder="Brief description of the project" rows="3" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="client_name" label="Client Name" required />
                    <flux:input wire:model="location" label="Location" placeholder="e.g., 123 Main St" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="Status" required>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="on_hold">On Hold</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="cancelled">Cancelled</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="manager_id" label="Project Manager" required>
                        @foreach($managers as $manager)
                            <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="planned_start_date" type="date" label="Planned Start Date" />
                    <flux:input wire:model="planned_end_date" type="date" label="Planned End Date" />
                </div>

                <flux:input wire:model="estimated_budget" type="number" step="0.01" label="Estimated Budget ($)" placeholder="0.00" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Create Project</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal name="edit-project" wire:model.self="showEditModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Project</flux:heading>
                <flux:text class="mt-2">Update project details.</flux:text>
            </div>

            <form wire:submit="updateProject" class="space-y-6">
                <flux:input wire:model="name" label="Project Name" placeholder="e.g., Luxury Villa Construction" required />
                
                <flux:textarea wire:model="description" label="Description" placeholder="Brief description of the project" rows="3" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="client_name" label="Client Name" required />
                    <flux:input wire:model="location" label="Location" placeholder="e.g., 123 Main St" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="Status" required>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="on_hold">On Hold</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="cancelled">Cancelled</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="manager_id" label="Project Manager" required>
                        @foreach($managers as $manager)
                            <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="planned_start_date" type="date" label="Planned Start Date" />
                    <flux:input wire:model="planned_end_date" type="date" label="Planned End Date" />
                </div>

                <flux:input wire:model="estimated_budget" type="number" step="0.01" label="Estimated Budget ($)" placeholder="0.00" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Update Project</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
