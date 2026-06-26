<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('projects.index') }}">Back</flux:button>
                    <flux:heading size="lg">{{ $project->name }}</flux:heading>
                </div>
                <div class="flex items-center gap-4">
                    @if($canManagePhases)
                        <flux:button variant="primary" wire:click="openPhaseModal" icon="plus">Add Phase</flux:button>
                    @endif
                    @if($canViewBoq)
                        <flux:button variant="primary" href="{{ route('projects.bill-of-quantities', $project->id) }}" icon="wrench">Manage BOQ</flux:button>
                    @endif
                    @if($canViewBudget)
                        <flux:button variant="ghost" href="{{ route('projects.budget', $project->id) }}" icon="currency-dollar">View Budget</flux:button>
                    @endif
                </div>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Client</div>
                    <div class="font-semibold">{{ $project->client->name }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                    <flux:badge :color="match($project->status) {
                        'active' => 'green',
                        'on_hold' => 'yellow',
                        'completed' => 'blue',
                        'cancelled' => 'red',
                        default => 'gray'
                    }">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </flux:badge>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overall Progress</div>
                    <div class="font-semibold">{{ number_format($project->overall_progress, 1) }}%</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Budget</div>
                    <div class="font-semibold">{{ $project->formatCurrency($project->estimated_budget ?? 0, 0) }}</div>
                </flux:card>
            </div>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-6">
            {{-- Project Details --}}
            @if($project->description || $project->location)
                <flux:card>
                    <flux:heading size="md" class="mb-4">Project Details</flux:heading>
                    <div class="space-y-2">
                        @if($project->description)
                            <p class="text-gray-700 dark:text-gray-300">{{ $project->description }}</p>
                        @endif
                        @if($project->location)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <flux:icon.map-pin class="w-4 h-4" />
                                <span>{{ $project->location }}</span>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- Overall Progress Bar --}}
            <flux:card>
                <div class="flex items-center justify-between mb-2">
                    <flux:heading size="md">Overall Progress</flux:heading>
                    <span class="text-lg font-bold">{{ number_format($project->overall_progress, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                    <div class="bg-blue-600 h-4 rounded-full transition-all" style="width: {{ $project->overall_progress }}%"></div>
                </div>
            </flux:card>

            {{-- Phases and Tasks --}}
            @forelse($project->phases as $phase)
                <flux:card>
                    <div class="space-y-4">
                        {{-- Phase Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <flux:heading size="md">{{ $phase->name }}</flux:heading>
                                    <flux:badge :color="match($phase->status) {
                                        'completed' => 'green',
                                        'in_progress' => 'blue',
                                        'pending' => 'gray',
                                        default => 'gray'
                                    }">
                                        {{ ucfirst(str_replace('_', ' ', $phase->status)) }}
                                    </flux:badge>
                                    <span class="text-sm text-gray-500">Weight: {{ $phase->weight }}%</span>
                                </div>
                                @if($phase->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $phase->description }}</p>
                                @endif
                            </div>
                            @if($canManagePhases)
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" icon-variant="mini" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEditPhaseModal({{ $phase->id }})">Edit Phase</flux:menu.item>
                                    @if($canCreateTasks)
                                        <flux:menu.item icon="plus" wire:click="openTaskModal({{ $phase->id }})">Add Task</flux:menu.item>
                                    @endif
                                    <flux:menu.item icon="trash" variant="danger" wire:click="deletePhase({{ $phase->id }})" wire:confirm="Delete this phase and all its tasks?">Delete Phase</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            @endif
                        </div>

                        {{-- Phase Progress --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium">Phase Progress</span>
                                <span class="text-sm font-semibold">{{ number_format($phase->progress, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-green-600 h-2 rounded-full transition-all" style="width: {{ $phase->progress }}%"></div>
                            </div>
                        </div>

                        {{-- Tasks --}}
                        @if($phase->tasks->count() > 0)
                            <div class="space-y-2">
                                @foreach($phase->tasks as $task)
                                    <div 
                                        class="flex items-center gap-3 p-3 rounded-lg border 
                                            {{ $task->status === 'completed' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' 
                                                : ($task->due() ? 'bg-red-200 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700') }} 
                                             hover:shadow-md transition-all
                                        "
                                        wire:click="openTaskProgress({{ $task->id }})"
                                    >
                                        {{-- Status Circle (Clickable) --}}
                                        @can('complete', $task)
                                            <button
                                                wire:click.stop="toggleTaskStatus({{ $task->getKey() }})"
                                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all hover:scale-110
                                                    {{ $task->status === 'completed' ? 'bg-green-500 border-green-600' : ($task->status === 'in_progress' ? 'bg-blue-500 border-blue-600' : 'bg-gray-200 border-gray-300 dark:bg-gray-700 dark:border-gray-600') }}"
                                                title="Click to toggle status"
                                            >
                                                @if($task->status === 'completed')
                                                    <flux:icon.check class="w-4 h-4 text-white" />
                                                @elseif($task->status === 'in_progress')
                                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                                @endif
                                            </button>
                                        @else
                                            <div 
                                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center
                                                    {{ $task->status === 'completed' ? 'bg-green-500 border-green-600' : ($task->status === 'in_progress' ? 'bg-blue-500 border-blue-600' : 'bg-gray-200 border-gray-300 dark:bg-gray-700 dark:border-gray-600') }}"
                                            >
                                                @if($task->status === 'completed')
                                                    <flux:icon.check class="w-4 h-4 text-white" />
                                                @elseif($task->status === 'in_progress')
                                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                                @endif
                                            </div>
                                        @endcan

                                        {{-- Task Details --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium {{ $task->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                                                    {{ $task->name }}
                                                </span>
                                                @if($task->weight > 0)
                                                    <span class="text-xs text-gray-500">{{ $task->weight }}%</span>
                                                @endif
                                            </div>
                                            @if($task->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $task->description }}</p>
                                            @endif
                                            <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                                @if($task->assignedUser)
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.user class="w-3 h-3" />
                                                        <span>{{ $task->assignedUser->name }}</span>
                                                    </div>
                                                @endif
                                                @if($task->estimated_cost)
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.currency-dollar class="w-3 h-3" />
                                                        <span>{{ $project->formatCurrency($task->estimated_cost, 0) }}</span>
                                                    </div>
                                                @endif
                                                @if($task->estimated_hours)
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.clock class="w-3 h-3" />
                                                        <span>{{ $task->estimated_hours }}h</span>
                                                    </div>
                                                @endif

                                                @if($task->planned_start_date)
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.calendar class="w-3 h-3" />
                                                        <span>{{ $task->planned_start_date->format('Y-m-d') }}</span>
                                                    </div>
                                                @endif
                                                @if($task->planned_end_date)
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.calendar class="w-3 h-3" />
                                                        <span>{{ $task->planned_end_date->format('Y-m-d') }}</span>
                                                    </div>
                                                @endif
                                                @if($task->due())
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.clock class="w-3 h-3" />
                                                        <span>Due</span>
                                                    </div>
                                                @endif


                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        @canany(['update', 'delete'], $task)
                                        <flux:dropdown align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" icon-variant="mini" wire:click.stop />
                                            <flux:menu>
                                                @can('update', $task)
                                                    <flux:menu.item icon="pencil" wire:click.stop="openEditTaskModal({{ $task->id }})">Edit</flux:menu.item>
                                                @endcan
                                                @can('delete', $task)
                                                    <flux:menu.item icon="trash" variant="danger" wire:click.stop="deleteTask({{ $task->id }})" wire:confirm="Delete this task?">Delete</flux:menu.item>
                                                @endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                        @endcanany
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <p>No tasks yet</p>
                                @if($canCreateTasks)
                                    <flux:button size="sm" variant="ghost" wire:click="openTaskModal({{ $phase->id }})" icon="plus" class="mt-2">
                                        Add First Task
                                    </flux:button>
                                @endif
                            </div>
                        @endif
                    </div>
                </flux:card>
            @empty
                <flux:card class="text-center py-12">
                    <flux:icon.bars-3 class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No phases yet</flux:heading>
                    <p class="text-gray-500 mb-4">Add phases to organize your project tasks</p>
                    @if($canManagePhases)
                        <flux:button variant="primary" wire:click="openPhaseModal" icon="plus">Add First Phase</flux:button>
                    @endif
                </flux:card>
            @endforelse
        </div>
    </flux:main>

    {{-- Phase Modal --}}
    <flux:modal name="phase-modal" wire:model.self="showPhaseModal" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingPhase ? 'Edit' : 'Add' }} Phase</flux:heading>
                <flux:text class="mt-2">{{ $editingPhase ? 'Update phase details.' : 'Add a new phase to organize tasks.' }}</flux:text>
            </div>

            <form wire:submit="savePhase" class="space-y-6">
                <flux:input wire:model="phase_name" label="Phase Name" placeholder="e.g., Foundation" required />
                
                <flux:textarea wire:model="phase_description" label="Description" placeholder="Brief description of this phase" rows="3" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="phase_order" type="number" label="Order" required />
                    <flux:input wire:model="phase_weight" type="number" step="0.01" label="Weight (%)" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="phase_planned_start_date" type="date" label="Planned Start" />
                    <flux:input wire:model="phase_planned_end_date" type="date" label="Planned End" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ $editingPhase ? 'Update' : 'Add' }} Phase</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Task Modal --}}
    <flux:modal name="task-modal" wire:model.self="showTaskModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingTask ? 'Edit' : 'Add' }} Task</flux:heading>
                <flux:text class="mt-2">{{ $editingTask ? 'Update task details.' : 'Add a new task to this phase.' }}</flux:text>
            </div>

            <form wire:submit="saveTask" class="space-y-6">
                <flux:input wire:model="task_name" label="Task Name" placeholder="e.g., Pour Foundation" required />
                
                <flux:textarea wire:model="task_description" label="Description" placeholder="Task details" rows="2" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="task_order" type="number" label="Order" required />
                    <flux:input wire:model="task_weight" type="number" step="0.01" label="Weight (%)" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="task_estimated_cost" type="number" step="0.01" label="Est. Cost ($)" />
                    <flux:input wire:model="task_estimated_hours" type="number" step="0.1" label="Est. Hours" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="task_assigned_to" label="Assign To" placeholder="Select user">
                        <flux:select.option value="">Unassigned</flux:select.option>
                        @foreach($users as $user)
                            <flux:select.option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="task_predecessor_id" label="Depends On" placeholder="No dependency">
                        <flux:select.option value="">No dependency</flux:select.option>
                        @foreach($availableTasks as $availableTask)
                            <flux:select.option value="{{ $availableTask->id }}">{{ $availableTask->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="task_planned_start_date" type="date" label="Planned Start" />
                    <flux:input wire:model="task_planned_end_date" type="date" label="Planned End" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ $editingTask ? 'Update' : 'Add' }} Task</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Task Progress Modal --}}
    <flux:modal 
        name="task-progress-modal" 
        wire:model.self="showProgressModal" 
        class="md:w-[700px]"
        variant="flyout" position="right"
    >
        <div class="flex h-full flex-col gap-6">
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">Task Progress</flux:heading>
                    @if($progressTask)
                        <flux:text class="mt-1 text-sm text-gray-500">{{ $progressTask->name }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="flex w-full flex-1 flex-col gap-6 min-h-0">
                <div class="flex flex-1 flex-col gap-4 min-h-0">
                    <flux:heading size="sm">Progress History</flux:heading>

                    @if($progressTask && $progressTask->comments->isNotEmpty())
                        <div class="flex-1 space-y-4 overflow-y-auto pr-2">
                            @foreach($progressTask->comments as $comment)
                                <flux:card>
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <p class="font-medium">{{ $comment->user->name ?? 'Unknown User' }}</p>
                                                <p class="text-xs text-gray-500">{{ $comment->created_at?->diffForHumans() }}</p>
                                            </div>
                                        </div>

                                        @if($comment->comment)
                                            <p class="text-sm text-gray-700">{{ $comment->comment }}</p>
                                        @endif

                                        @if($comment->media_files)
                                            <div class="grid gap-2">
                                                @foreach($comment->media_files as $media)
                                                    @php
                                                        $isImage = str_starts_with($media['mime_type'] ?? '', 'image/');
                                                        $isVideo = str_starts_with($media['mime_type'] ?? '', 'video/');
                                                    @endphp
                                                    <div class="border border-gray-200 rounded-md bg-gray-50 p-2">
                                                        <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
                                                            <span>{{ $media['name'] ?? 'Attachment' }}</span>
                                                            <a
                                                                href="{{ Storage::disk('public')->url($media['path'] ?? '') }}"
                                                                target="_blank"
                                                                class="text-blue-600 hover:underline"
                                                            >View</a>
                                                        </div>

                                                        @if($isImage)
                                                            <img
                                                                src="{{ Storage::disk('public')->url($media['path'] ?? '') }}"
                                                                alt="Task progress image"
                                                                class="max-h-40 rounded-md object-cover"
                                                            >
                                                        @elseif($isVideo)
                                                            <video
                                                                controls
                                                                class="w-full rounded-md"
                                                                src="{{ Storage::disk('public')->url($media['path'] ?? '') }}"
                                                            ></video>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </flux:card>
                            @endforeach
                        </div>
                    @else
                        <div class="flex-1 text-sm text-gray-500">No progress updates yet. Add the first update to keep stakeholders informed.</div>
                    @endif
                </div>

                <div class="space-y-4 border-t border-gray-200 pt-4 flex-shrink-0 bg-white/90 dark:border-gray-700 dark:bg-gray-900/90">
                    <flux:heading size="sm">Add Update</flux:heading>

                    <flux:textarea
                        wire:model.defer="progress_comment"
                        label="Comment"
                        placeholder="Describe progress, blockers, or next steps"
                        rows="4"
                    />

                    <div class="space-y-4">
                        <flux:heading size="xs" class="text-gray-500">Media Attachments</flux:heading>
                        <flux:input
                            wire:model="progress_media"
                            type="file"
                            label="Upload images or videos"
                            multiple
                            helper="Supported: jpg, png, gif, webp, mp4, mov, avi, wmv. Max 50MB each."
                        />
                        @error('progress_media.*')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" wire:click="closeProgressModal">Cancel</flux:button>
                        <flux:button variant="primary" wire:click="saveTaskProgress">Save Update</flux:button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
