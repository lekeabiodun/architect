<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Inspector Dashboard</flux:heading>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending Inspections</div>
                    <div class="font-semibold text-2xl text-yellow-600">{{ $stats['pending'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Passed Today</div>
                    <div class="font-semibold text-2xl text-green-600">{{ $stats['passed_today'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Failed Today</div>
                    <div class="font-semibold text-2xl text-red-600">{{ $stats['failed_today'] }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Materials to Confirm</div>
                    <div class="font-semibold text-2xl text-blue-600">{{ $stats['materials_pending'] }}</div>
                </flux:card>
            </div>

            {{-- Filter --}}
            <flux:select wire:model.live="filter_project" placeholder="All Projects" class="w-64">
                <flux:select.option value="">All Projects</flux:select.option>
                @foreach($projects as $project)
                    <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-6">
            {{-- Pending Task Inspections --}}
            <div>
                <flux:heading size="md" class="mb-4">Tasks Requiring Inspection</flux:heading>
                
                @forelse($pendingTasks as $task)
                    <flux:card class="mb-4">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <flux:heading size="md">{{ $task->name }}</flux:heading>
                                        <flux:badge :color="$task->inspection_status === 're_inspection' ? 'red' : 'yellow'">
                                            {{ $task->inspection_status === 're_inspection' ? 'Re-Inspection Required' : 'Pending Inspection' }}
                                        </flux:badge>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $task->phase->project->name }} → {{ $task->phase->name }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <div class="text-xs text-gray-500">Assigned To</div>
                                    <div class="font-medium">{{ $task->assignedUser->name ?? 'Unassigned' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Completed On</div>
                                    <div class="font-medium">{{ $task->actual_end_date?->format('M d, Y') ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Estimated Cost</div>
                                    <div class="font-medium">${{ number_format($task->estimated_cost ?? 0, 0) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Actual Cost</div>
                                    <div class="font-medium">${{ number_format($task->actual_cost ?? 0, 0) }}</div>
                                </div>
                            </div>

                            @if($task->description)
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Description:</div>
                                    <p class="text-sm">{{ $task->description }}</p>
                                </div>
                            @endif

                            @if($task->inspection_status === 're_inspection' && $task->inspector_feedback)
                                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                    <div class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Previous Inspection Feedback:</div>
                                    <p class="text-sm text-red-700 dark:text-red-300">{{ $task->inspector_feedback }}</p>
                                </div>
                            @endif

                            <div class="flex gap-2 pt-4 border-t dark:border-gray-700">
                                <flux:button 
                                    size="sm" 
                                    variant="primary"
                                    wire:click="selectTask({{ $task->id }})"
                                    x-data
                                    @click="$dispatch('open-modal', 'approve-modal-{{ $task->id }}')"
                                >
                                    <flux:icon.check class="w-4 h-4 mr-1" />
                                    Pass Inspection
                                </flux:button>
                                <flux:button 
                                    size="sm" 
                                    variant="danger"
                                    wire:click="selectTask({{ $task->id }})"
                                    x-data
                                    @click="$dispatch('open-modal', 'fail-modal-{{ $task->id }}')"
                                >
                                    <flux:icon.x-mark class="w-4 h-4 mr-1" />
                                    Fail Inspection
                                </flux:button>
                            </div>
                        </div>

                        {{-- Approve Modal --}}
                        <flux:modal name="approve-modal-{{ $task->id }}" class="md:w-[500px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Approve Inspection</flux:heading>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Task: <strong>{{ $task->name }}</strong>
                                </p>
                                <flux:textarea 
                                    wire:model="inspection_feedback" 
                                    label="Inspection Notes (Optional)" 
                                    placeholder="Add any notes about the inspection..."
                                    rows="3" 
                                />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="approveTask({{ $task->id }})">
                                        Approve & Pass
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>

                        {{-- Fail Modal --}}
                        <flux:modal name="fail-modal-{{ $task->id }}" class="md:w-[500px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Fail Inspection</flux:heading>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Task: <strong>{{ $task->name }}</strong>
                                </p>
                                <flux:textarea 
                                    wire:model="inspection_feedback" 
                                    label="Failure Reason (Required)" 
                                    placeholder="Explain why the task failed inspection and what needs to be corrected..."
                                    rows="4" 
                                    required
                                />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="danger" wire:click="failTask({{ $task->id }})">
                                        Fail Inspection
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:card>
                @empty
                    <flux:card class="text-center py-12">
                        <flux:icon.check-badge class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">No pending inspections</flux:heading>
                        <p class="text-gray-500">All tasks have been inspected</p>
                    </flux:card>
                @endforelse

                <div class="mt-4">
                    {{ $pendingTasks->links() }}
                </div>
            </div>

            {{-- Material Deliveries to Confirm --}}
            @if($pendingMaterials->count() > 0)
                <div>
                    <flux:heading size="md" class="mb-4">Material Deliveries to Confirm</flux:heading>
                    
                    @foreach($pendingMaterials as $request)
                        <flux:card class="mb-4">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <flux:heading size="md">{{ $request->material->name }}</flux:heading>
                                            <flux:badge color="blue">Awaiting Confirmation</flux:badge>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->project->name }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-500">Disbursed Quantity</div>
                                        <div class="font-medium">{{ number_format($request->disbursed_quantity, 2) }} {{ $request->material->unit }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Disbursed By</div>
                                        <div class="font-medium">{{ $request->disburser->name }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Disbursed On</div>
                                        <div class="font-medium">{{ $request->disbursed_at->format('M d, Y') }}</div>
                                    </div>
                                </div>

                                <flux:button 
                                    size="sm" 
                                    variant="primary"
                                    x-data
                                    @click="$dispatch('open-modal', 'confirm-material-{{ $request->id }}')"
                                >
                                    <flux:icon.check-badge class="w-4 h-4 mr-1" />
                                    Confirm Delivery
                                </flux:button>
                            </div>

                            <flux:modal name="confirm-material-{{ $request->id }}" class="md:w-[500px]">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Confirm Material Delivery</flux:heading>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Material: <strong>{{ $request->material->name }}</strong><br>
                                        Quantity: <strong>{{ number_format($request->disbursed_quantity, 2) }} {{ $request->material->unit }}</strong>
                                    </p>
                                    <flux:textarea 
                                        wire:model="inspection_feedback" 
                                        label="Confirmation Notes" 
                                        placeholder="Confirm materials received and in good condition..."
                                        rows="3" 
                                        required
                                    />
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="primary" wire:click="confirmMaterialDelivery({{ $request->id }})">
                                            Confirm Delivery
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:card>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:main>
</div>
