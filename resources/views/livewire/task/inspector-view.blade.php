<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Task Inspection</flux:heading>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</div>
                    <div class="font-semibold text-2xl">{{ $stats['total'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending Inspection</div>
                    <div class="font-semibold text-2xl text-orange-600">{{ $stats['pending_inspection'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Passed</div>
                    <div class="font-semibold text-2xl text-green-600">{{ $stats['passed'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Failed</div>
                    <div class="font-semibold text-2xl text-red-600">{{ $stats['failed'] }}</div>
                </flux:card>
            </div>

            {{-- Filters --}}
            <div class="flex gap-2">
                <flux:select wire:model.live="filter_status" placeholder="All Status" class="w-48">
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="in_progress">In Progress</flux:select.option>
                    <flux:select.option value="completed">Completed</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filter_inspection" placeholder="All Inspection Status" class="w-56">
                    <flux:select.option value="">All Inspection Status</flux:select.option>
                    <flux:select.option value="pending">Pending Inspection</flux:select.option>
                    <flux:select.option value="passed">Passed</flux:select.option>
                    <flux:select.option value="failed">Failed</flux:select.option>
                    <flux:select.option value="re_inspection">Re-inspection</flux:select.option>
                </flux:select>
            </div>
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
                                    @elseif($task->inspection_status === 're_inspection')
                                        <flux:badge color="orange">Re-inspection Required</flux:badge>
                                    @elseif($task->status === 'completed' && !$task->inspection_status)
                                        <flux:badge color="orange">Pending Inspection</flux:badge>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $task->phase->project->name }} → {{ $task->phase->name }}
                                </p>
                                @if($task->assignedUser)
                                    <p class="text-xs text-gray-500 mt-1">
                                        Assigned to: <strong>{{ $task->assignedUser->name }}</strong>
                                    </p>
                                @endif
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
                                <div class="text-xs text-gray-500">Actual End</div>
                                <div class="font-medium">{{ $task->actual_end_date?->format('M d, Y') ?? 'N/A' }}</div>
                            </div>
                            @unless(auth()->user()->isClient())
                                <div>
                                    <div class="text-xs text-gray-500">Estimated Hours</div>
                                    <div class="font-medium">{{ $task->estimated_hours ?? 'N/A' }}h</div>
                                </div>
                            @endunless
                        </div>

                        @if($task->description)
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Description:</div>
                                <p class="text-sm">{{ $task->description }}</p>
                            </div>
                        @endif

                        {{-- Previous Inspection Feedback --}}
                        @if($task->inspector_feedback)
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon.information-circle class="w-5 h-5 text-blue-600" />
                                    <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">Previous Feedback:</div>
                                </div>
                                <p class="text-sm text-blue-700 dark:text-blue-300">{{ $task->inspector_feedback }}</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                    By {{ $task->inspector->name }} on {{ $task->inspected_at->format('M d, Y') }}
                                </p>
                            </div>
                        @endif

                        {{-- Comments Section --}}
                        @if($task->comments->count() > 0)
                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm font-medium mb-3">Comments & Updates ({{ $task->comments->count() }})</div>
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
                            @if($task->status === 'completed' && in_array($task->inspection_status, ['pending', 're_inspection', null]))
                                <flux:modal.trigger name="inspect-modal-{{ $task->id }}">
                                    <flux:button
                                        size="sm"
                                        variant="filled"
                                        wire:click="selectTask({{ $task->id }})"
                                    >
                                        <flux:icon.clipboard-document-check class="w-4 h-4 mr-1" />
                                        Inspect Task
                                    </flux:button>
                                </flux:modal.trigger>
                            @endif

                            <flux:modal.trigger name="status-modal-{{ $task->id }}">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                >
                                    <flux:icon.arrow-path class="w-4 h-4 mr-1" />
                                    Change Status
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:modal.trigger name="comment-modal-{{ $task->id }}">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="selectTask({{ $task->id }})"
                                >
                                    <flux:icon.chat-bubble-left class="w-4 h-4 mr-1" />
                                    Add Comment
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:modal.trigger name="details-modal-{{ $task->id }}">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="selectTask({{ $task->id }})"
                                >
                                    <flux:icon.information-circle class="w-4 h-4 mr-1" />
                                    View Details
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    </div>

                    {{-- Inspect Modal --}}
                    <flux:modal name="inspect-modal-{{ $task->id }}" class="md:w-[600px]">
                        <div class="space-y-4">
                            <flux:heading size="lg">Inspect Task</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Task: <strong>{{ $task->name }}</strong>
                            </p>
                            
                            <flux:textarea 
                                wire:model="inspection_feedback" 
                                label="Inspection Feedback" 
                                placeholder="Provide detailed feedback about the task quality and completion..."
                                rows="4" 
                                required
                            />
                            @error('inspection_feedback') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                <flux:button 
                                    variant="danger" 
                                    wire:click="inspectTask({{ $task->id }}, 'failed')"
                                    wire:confirm="Are you sure you want to fail this task inspection?"
                                >
                                    <flux:icon.x-circle class="w-4 h-4 mr-1" />
                                    Fail Inspection
                                </flux:button>
                                <flux:button 
                                    variant="primary" 
                                    wire:click="inspectTask({{ $task->id }}, 'passed')"
                                >
                                    <flux:icon.check-circle class="w-4 h-4 mr-1" />
                                    Pass Inspection
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    {{-- Status Change Modal --}}
                    <flux:modal name="status-modal-{{ $task->id }}" class="md:w-[500px]">
                        <div class="space-y-4">
                            <flux:heading size="lg">Change Task Status</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Task: <strong>{{ $task->name }}</strong><br>
                                Current Status: <strong>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</strong>
                            </p>

                            <div class="space-y-2">
                                <flux:button 
                                    variant="outline" 
                                    class="w-full"
                                    wire:click="updateTaskStatus({{ $task->id }}, 'pending')"
                                    :disabled="$task->status === 'pending'"
                                >
                                    Set as Pending
                                </flux:button>
                                <flux:button 
                                    variant="outline" 
                                    class="w-full"
                                    wire:click="updateTaskStatus({{ $task->id }}, 'in_progress')"
                                    :disabled="$task->status === 'in_progress'"
                                >
                                    Set as In Progress
                                </flux:button>
                                <flux:button 
                                    variant="outline" 
                                    class="w-full"
                                    wire:click="updateTaskStatus({{ $task->id }}, 'completed')"
                                    :disabled="$task->status === 'completed'"
                                >
                                    Set as Completed
                                </flux:button>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <flux:spacer />
                                <flux:modal.close><flux:button variant="primary">Close</flux:button></flux:modal.close>
                            </div>
                        </div>
                    </flux:modal>

                    {{-- Comment Modal --}}
                    <flux:modal name="comment-modal-{{ $task->id }}" class="md:w-[500px]">
                        <div class="space-y-4">
                            <flux:heading size="lg">Add Comment</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Task: <strong>{{ $task->name }}</strong>
                            </p>
                            <flux:textarea 
                                wire:model="comment_text" 
                                label="Comment" 
                                placeholder="Add your comment or note about this task..."
                                rows="4" 
                                required
                            />
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Attach Images/Videos (Optional)</label>
                                <input 
                                    type="file" 
                                    wire:model="media_files" 
                                    multiple 
                                    accept="image/*,video/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                @error('media_files.*') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-500 mt-1">Max 50MB per file. Supported: JPG, PNG, GIF, MP4, MOV, AVI</p>
                                
                                @if($media_files)
                                    <div class="mt-2">
                                        <div class="text-xs font-medium text-gray-600 mb-1">Files selected:</div>
                                        @foreach($media_files as $file)
                                            <div class="text-xs text-gray-600">• {{ $file->getClientOriginalName() }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                <flux:button variant="primary" wire:click="addComment({{ $task->id }})">
                                    Add Comment
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    {{-- Details Modal --}}
                    <flux:modal name="details-modal-{{ $task->id }}" class="md:w-[600px]">
                        <div class="space-y-4">
                            <flux:heading size="lg">{{ $task->name }}</flux:heading>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs text-gray-500">Status</div>
                                    <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Inspection Status</div>
                                    <div class="font-medium">{{ $task->inspection_status ? ucfirst($task->inspection_status) : 'Not inspected' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Assigned To</div>
                                    <div class="font-medium">{{ $task->assignedUser?->name ?? 'Unassigned' }}</div>
                                </div>
                                @unless(auth()->user()->isClient())
                                    <div>
                                        <div class="text-xs text-gray-500">Estimated Cost</div>
                                        <div class="font-medium">${{ number_format($task->estimated_cost ?? 0, 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Estimated Hours</div>
                                        <div class="font-medium">{{ $task->estimated_hours ?? 'N/A' }}h</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Actual Hours</div>
                                        <div class="font-medium">{{ $task->actual_hours ?? 'N/A' }}h</div>
                                    </div>
                                @endunless
                            </div>

                            @if($task->description)
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Description:</div>
                                    <p class="text-sm">{{ $task->description }}</p>
                                </div>
                            @endif

                            @if($task->predecessor)
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Depends On:</div>
                                    <p class="text-sm">{{ $task->predecessor->name }}</p>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close><flux:button variant="primary">Close</flux:button></flux:modal.close>
                            </div>
                        </div>
                    </flux:modal>
                </flux:card>
            @empty
                <flux:card class="text-center py-12">
                    <flux:icon.clipboard-document-check class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No tasks to inspect</flux:heading>
                    <p class="text-gray-500">There are no tasks available for inspection</p>
                </flux:card>
            @endforelse

            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        </div>
    </flux:main>
</div>
