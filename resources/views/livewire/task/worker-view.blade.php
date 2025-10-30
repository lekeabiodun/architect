<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">My Tasks</flux:heading>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</div>
                    <div class="font-semibold text-2xl">{{ $stats['total'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
                    <div class="font-semibold text-2xl text-gray-600">{{ $stats['pending'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">In Progress</div>
                    <div class="font-semibold text-2xl text-blue-600">{{ $stats['in_progress'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                    <div class="font-semibold text-2xl text-green-600">{{ $stats['completed'] }}</div>
                </flux:card>
            </div>

            {{-- Filter --}}
            <flux:select wire:model.live="filter_status" placeholder="All Status" class="w-48">
                <flux:select.option value="">All Status</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="in_progress">In Progress</flux:select.option>
                <flux:select.option value="completed">Completed</flux:select.option>
            </flux:select>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-4">
            @forelse($tasks as $task)
                <flux:card>
                    <div class="space-y-4">
                        {{-- Task Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:heading size="md">{{ $task->name }}</flux:heading>
                                    <flux:badge :color="match($task->status) {
                                        'completed' => 'green',
                                        'in_progress' => 'blue',
                                        'pending' => 'gray',
                                        default => 'gray'
                                    }">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </flux:badge>
                                    @if($task->inspection_status === 'failed')
                                        <flux:badge color="red">Failed Inspection</flux:badge>
                                    @elseif($task->inspection_status === 'passed')
                                        <flux:badge color="green">Inspection Passed</flux:badge>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $task->phase->project->name }} → {{ $task->phase->name }}
                                </p>
                            </div>
                        </div>

                        {{-- Task Details --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <div class="text-xs text-gray-500">Planned Start</div>
                                <div class="font-medium">{{ $task->planned_start_date?->format('M d, Y') ?? 'Not set' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Planned End</div>
                                <div class="font-medium">{{ $task->planned_end_date?->format('M d, Y') ?? 'Not set' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Estimated Hours</div>
                                <div class="font-medium">{{ $task->estimated_hours ?? 'N/A' }}h</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Priority</div>
                                <div class="font-medium">{{ ucfirst($task->priority ?? 'normal') }}</div>
                            </div>
                        </div>

                        @if($task->description)
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Description:</div>
                                <p class="text-sm">{{ $task->description }}</p>
                            </div>
                        @endif

                        {{-- Inspector Feedback if Failed --}}
                        @if($task->inspection_status === 'failed' && $task->inspector_feedback)
                            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon.exclamation-triangle class="w-5 h-5 text-red-600" />
                                    <div class="text-sm font-semibold text-red-600 dark:text-red-400">Inspector Feedback:</div>
                                </div>
                                <p class="text-sm text-red-700 dark:text-red-300">{{ $task->inspector_feedback }}</p>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                                    Inspected by {{ $task->inspector->name }} on {{ $task->inspected_at->format('M d, Y') }}
                                </p>
                            </div>
                        @endif

                        {{-- Comments Section --}}
                        @if($task->comments->count() > 0)
                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm font-medium mb-3">Comments ({{ $task->comments->count() }})</div>
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    @foreach($task->comments as $comment)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded text-sm">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-medium">{{ $comment->user->name }}</span>
                                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 mb-2">{{ $comment->comment }}</p>
                                            
                                            {{-- Display Media Files --}}
                                            @if($comment->media_files && count($comment->media_files) > 0)
                                                <div class="mt-2 grid grid-cols-2 gap-2">
                                                    @foreach($comment->media_files as $media)
                                                        @if(str_starts_with($media['mime_type'], 'image/'))
                                                            <div class="relative group">
                                                                <img src="{{ Storage::url($media['path']) }}" alt="{{ $media['name'] }}" class="rounded w-full h-32 object-cover border border-gray-200 dark:border-gray-700">
                                                                <a href="{{ Storage::url($media['path']) }}" target="_blank" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all flex items-center justify-center">
                                                                    <flux:icon.magnifying-glass class="w-6 h-6 text-white opacity-0 group-hover:opacity-100" />
                                                                </a>
                                                            </div>
                                                        @elseif(str_starts_with($media['mime_type'], 'video/'))
                                                            <div class="relative">
                                                                <video controls class="rounded w-full h-32 object-cover border border-gray-200 dark:border-gray-700">
                                                                    <source src="{{ Storage::url($media['path']) }}" type="{{ $media['mime_type'] }}">
                                                                    Your browser does not support the video tag.
                                                                </video>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex gap-2 pt-4 border-t dark:border-gray-700">
                            @if($task->status === 'pending')
                                <flux:button 
                                    size="sm" 
                                    variant="primary"
                                    wire:click="startTask({{ $task->id }})"
                                >
                                    <flux:icon.play class="w-4 h-4 mr-1" />
                                    Start Task
                                </flux:button>
                            @elseif($task->status === 'in_progress')
                                <flux:button 
                                    size="sm" 
                                    variant="primary"
                                    wire:click="completeTask({{ $task->id }})"
                                    wire:confirm="Mark this task as completed?"
                                >
                                    <flux:icon.check class="w-4 h-4 mr-1" />
                                    Complete Task
                                </flux:button>
                            @endif

                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openProgressModal({{ $task->id }})"
                            >
                                <flux:icon.chat-bubble-left class="w-4 h-4 mr-1" />
                                Update Progress
                            </flux:button>

                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openDetailsModal({{ $task->id }})"
                            >
                                <flux:icon.information-circle class="w-4 h-4 mr-1" />
                                View Details
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @empty
                <flux:card class="text-center py-12">
                    <flux:icon.clipboard-document-list class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No tasks assigned</flux:heading>
                    <p class="text-gray-500">You don't have any tasks assigned yet</p>
                </flux:card>
            @endforelse

            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        </div>
    </flux:main>

    {{-- Task Progress Modal --}}
    <flux:modal 
        wire:model.self="showProgressModal" 
        class="md:w-[700px]"
        variant="flyout" position="right"
    >
        @if($selectedTask)
            <div class="flex h-full flex-col gap-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Task Progress</flux:heading>
                        <flux:text class="mt-1 text-sm text-gray-500">{{ $selectedTask->name }}</flux:text>
                    </div>
                    <flux:button variant="ghost" icon="x-mark" wire:click="closeProgressModal" />
                </div>

                <div class="flex w-full flex-1 flex-col gap-6 min-h-0">
                    <div class="flex flex-1 flex-col gap-4 min-h-0">
                        <flux:heading size="sm">Progress History</flux:heading>

                        @if($selectedTask->comments->isNotEmpty())
                            <div class="flex-1 space-y-4 overflow-y-auto pr-2">
                                @foreach($selectedTask->comments as $comment)
                                    <flux:card>
                                        <div class="space-y-3">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <p class="font-medium">{{ $comment->user->name ?? 'Unknown User' }}</p>
                                                    <p class="text-xs text-gray-500">{{ $comment->created_at?->diffForHumans() }}</p>
                                                </div>
                                            </div>

                                            @if($comment->comment)
                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $comment->comment }}</p>
                                            @endif

                                            @if($comment->media_files)
                                                <div class="grid gap-2">
                                                    @foreach($comment->media_files as $media)
                                                        @php
                                                            $isImage = str_starts_with($media['mime_type'] ?? '', 'image/');
                                                            $isVideo = str_starts_with($media['mime_type'] ?? '', 'video/');
                                                        @endphp
                                                        <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900/60 p-2">
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
                                                                    class="max-h-40 w-full rounded-md object-cover"
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
                        @error('progress_comment')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

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
        @endif
    </flux:modal>

    {{-- Details Modal --}}
    <flux:modal wire:model="showDetailsModal" class="md:w-[600px]">
        @if($selectedTask)
            <div class="space-y-4">
                <flux:heading size="lg">{{ $selectedTask->name }}</flux:heading>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Status</div>
                        <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $selectedTask->status)) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Inspection Status</div>
                        <div class="font-medium">{{ $selectedTask->inspection_status ? ucfirst($selectedTask->inspection_status) : 'Not inspected' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Estimated Cost</div>
                        <div class="font-medium">${{ number_format($selectedTask->estimated_cost ?? 0, 0) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Estimated Hours</div>
                        <div class="font-medium">{{ $selectedTask->estimated_hours ?? 'N/A' }}h</div>
                    </div>
                </div>

                @if($selectedTask->description)
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Description:</div>
                        <p class="text-sm">{{ $selectedTask->description }}</p>
                    </div>
                @endif

                @if($selectedTask->predecessor)
                    <div>
                        <div class="text-xs font-medium text-gray-500 mb-1">Depends On:</div>
                        <p class="text-sm">{{ $selectedTask->predecessor->name }}</p>
                    </div>
                @endif

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="primary" wire:click="closeDetailsModal">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
