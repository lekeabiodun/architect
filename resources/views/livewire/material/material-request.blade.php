<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Material Requests</flux:heading>
                @can('create', App\Models\MaterialRequest::class)
                    <flux:button variant="primary" wire:click="openRequestModal" icon="plus">New Request</flux:button>
                @endcan
            </div>

            {{-- Filters --}}
            <div class="flex gap-4">
                <flux:select wire:model.live="status_filter" placeholder="All Status" class="w-48">
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="approved">Approved</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                    <flux:select.option value="disbursed">Disbursed</flux:select.option>
                    <flux:select.option value="confirmed">Confirmed</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="project_filter" placeholder="All Projects" class="w-64">
                    <flux:select.option value="">All Projects</flux:select.option>
                    @foreach($projects as $project)
                        <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-4">
            @forelse($requests as $request)
                <flux:card>
                    <div class="space-y-4">
                        {{-- Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:heading size="md">{{ $request->material->name }}</flux:heading>
                                    <flux:badge :color="match($request->status) {
                                        'pending' => 'yellow',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'disbursed' => 'blue',
                                        'confirmed' => 'green',
                                        'cancelled' => 'gray',
                                        default => 'gray'
                                    }">
                                        {{ ucfirst($request->status) }}
                                    </flux:badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $request->project->name }}
                                    @if($request->phase) → {{ $request->phase->name }} @endif
                                    @if($request->task) → {{ $request->task->name }} @endif
                                </p>
                            </div>
                        </div>

                        {{-- Request Details --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <div class="text-xs text-gray-500">Requested Quantity</div>
                                <div class="font-medium">{{ number_format($request->requested_quantity, 2) }} {{ $request->material->unit }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Required Date</div>
                                <div class="font-medium">{{ $request->required_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Requested By</div>
                                <div class="font-medium">{{ $request->requester->name }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Request Date</div>
                                <div class="font-medium">{{ $request->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>

                        {{-- Purpose --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 mb-1">Purpose:</div>
                            <p class="text-sm">{{ $request->purpose }}</p>
                            @if($request->justification)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $request->justification }}</p>
                            @endif
                        </div>

                        {{-- Workflow Progress --}}
                        <div class="pt-4 border-t dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $request->status !== 'pending' ? 'bg-green-500 text-white' : 'bg-gray-300' }}">
                                        <flux:icon.check class="w-4 h-4" />
                                    </div>
                                    <span class="font-medium">Request</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 {{ in_array($request->status, ['approved', 'disbursed', 'confirmed']) ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($request->status, ['approved', 'disbursed', 'confirmed']) ? 'bg-green-500 text-white' : 'bg-gray-300' }}">
                                        <flux:icon.check class="w-4 h-4" />
                                    </div>
                                    <span class="font-medium">Approve</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 {{ in_array($request->status, ['disbursed', 'confirmed']) ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($request->status, ['disbursed', 'confirmed']) ? 'bg-blue-500 text-white' : 'bg-gray-300' }}">
                                        <flux:icon.truck class="w-4 h-4" />
                                    </div>
                                    <span class="font-medium">Disburse</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 {{ $request->status === 'confirmed' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $request->status === 'confirmed' ? 'bg-green-500 text-white' : 'bg-gray-300' }}">
                                        <flux:icon.check-badge class="w-4 h-4" />
                                    </div>
                                    <span class="font-medium">Confirm</span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions based on status --}}
                        @if($request->status === 'pending')
                            <div class="flex gap-2 pt-4 border-t dark:border-gray-700">
                                @can('approve', $request)
                                    <flux:button 
                                        size="sm" 
                                        variant="primary"
                                        wire:click="selectRequest({{ $request->id }})"
                                        x-data
                                        @click="$dispatch('open-modal', 'approve-modal-{{ $request->id }}')"
                                    >
                                        Approve Request
                                    </flux:button>
                                    <flux:button 
                                        size="sm" 
                                        variant="danger"
                                        wire:click="selectRequest({{ $request->id }})"
                                        x-data
                                        @click="$dispatch('open-modal', 'reject-modal-{{ $request->id }}')"
                                    >
                                        Reject
                                    </flux:button>
                                @endcan
                            </div>
                        @elseif($request->status === 'approved')
                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Approved by {{ $request->approver->name }} on {{ $request->approved_at->format('M d, Y') }}
                                    - Qty: {{ number_format($request->approved_quantity, 2) }} {{ $request->material->unit }}
                                </div>
                                @can('disburse', $request)
                                    <flux:button 
                                        size="sm" 
                                        variant="primary"
                                        wire:click="selectRequest({{ $request->id }})"
                                        x-data
                                        @click="$dispatch('open-modal', 'disburse-modal-{{ $request->id }}')"
                                    >
                                        Disburse Materials
                                    </flux:button>
                                @endcan
                            </div>
                        @elseif($request->status === 'disbursed')
                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Disbursed by {{ $request->disburser->name }} on {{ $request->disbursed_at->format('M d, Y') }}
                                    - Qty: {{ number_format($request->disbursed_quantity, 2) }} {{ $request->material->unit }}
                                </div>
                                @can('confirm', $request)
                                    <flux:button 
                                        size="sm" 
                                        variant="primary"
                                        wire:click="selectRequest({{ $request->id }})"
                                        x-data
                                        @click="$dispatch('open-modal', 'confirm-modal-{{ $request->id }}')"
                                    >
                                        Confirm Delivery
                                    </flux:button>
                                @endcan
                            </div>
                        @elseif($request->status === 'confirmed')
                            <div class="pt-4 border-t dark:border-gray-700 text-sm text-green-600 dark:text-green-400">
                                ✓ Confirmed by {{ $request->confirmer->name }} on {{ $request->confirmed_at->format('M d, Y') }}
                            </div>
                        @elseif($request->status === 'rejected')
                            <div class="pt-4 border-t dark:border-gray-700 text-sm text-red-600 dark:text-red-400">
                                ✗ Rejected by {{ $request->approver->name }} on {{ $request->approved_at->format('M d, Y') }}
                                @if($request->approval_notes)
                                    <p class="mt-1">Reason: {{ $request->approval_notes }}</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Inline Modals for Actions --}}
                    @can('approve', $request)
                        <flux:modal name="approve-modal-{{ $request->id }}" class="md:w-[400px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Approve Request</flux:heading>
                                <flux:input wire:model="approved_quantity" type="number" step="0.01" label="Approved Quantity" required />
                                <flux:textarea wire:model="approval_notes" label="Notes" rows="2" />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="approveRequest({{ $request->id }})">Approve</flux:button>
                                </div>
                            </div>
                        </flux:modal>

                        <flux:modal name="reject-modal-{{ $request->id }}" class="md:w-[400px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Reject Request</flux:heading>
                                <flux:textarea wire:model="approval_notes" label="Rejection Reason" rows="3" required />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="danger" wire:click="rejectRequest({{ $request->id }})">Reject</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endcan

                    @can('disburse', $request)
                        <flux:modal name="disburse-modal-{{ $request->id }}" class="md:w-[400px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Disburse Materials</flux:heading>
                                <flux:input wire:model="disbursed_quantity" type="number" step="0.01" label="Disbursed Quantity" required />
                                <flux:textarea wire:model="disbursement_notes" label="Disbursement Notes" rows="2" />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="disburseRequest({{ $request->id }})">Disburse</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endcan

                    @can('confirm', $request)
                        <flux:modal name="confirm-modal-{{ $request->id }}" class="md:w-[400px]">
                            <div class="space-y-4">
                                <flux:heading size="lg">Confirm Delivery</flux:heading>
                                <flux:textarea wire:model="confirmation_notes" label="Confirmation Notes" rows="3" required />
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                    <flux:button variant="primary" wire:click="confirmRequest({{ $request->id }})">Confirm</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endcan
                </flux:card>
            @empty
                <flux:card class="text-center py-12">
                    <flux:icon.inbox class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No material requests yet</flux:heading>
                    <p class="text-gray-500 mb-4">Create a request to get materials for your project</p>
                    @can('create', App\Models\MaterialRequest::class)
                        <flux:button variant="primary" wire:click="openRequestModal" icon="plus">Create First Request</flux:button>
                    @endcan
                </flux:card>
            @endforelse

            {{ $requests->links() }}
        </div>
    </flux:main>

    {{-- New Request Modal --}}
    <flux:modal name="request-modal" wire:model.self="showRequestModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">New Material Request</flux:heading>
                <flux:text class="mt-2">Submit a request for project materials</flux:text>
            </div>

            <form wire:submit="saveRequest" class="space-y-6">
                <flux:select wire:model="request_material_id" label="Material" required>
                    <flux:select.option value="">Select Material</flux:select.option>
                    @foreach($materials as $material)
                        <flux:select.option value="{{ $material->id }}">
                            {{ $material->name }} ({{ $material->code }}) - {{ $material->formatCurrency($material->unit_cost, 2) }}/{{ $material->unit }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="request_project_id" label="Project" required>
                    <flux:select.option value="">Select Project</flux:select.option>
                    @foreach($projects as $project)
                        <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="request_quantity" type="number" step="0.01" label="Quantity" required />
                    <flux:input wire:model="request_required_date" type="date" label="Required Date" required />
                </div>

                <flux:input wire:model="request_purpose" label="Purpose" placeholder="e.g., Foundation work" required />
                <flux:textarea wire:model="request_justification" label="Justification" placeholder="Why this material is needed" rows="3" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Submit Request</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
