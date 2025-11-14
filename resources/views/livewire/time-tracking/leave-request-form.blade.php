<div>
    <!-- Leave Balance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach($leaveBalances as $type => $balance)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">{{ $balance->leave_type_name }}</div>
                <div class="text-2xl font-bold text-blue-600">{{ number_format($balance->available_days, 1) }}</div>
                <div class="text-xs text-gray-500">
                    {{ number_format($balance->balance_days, 1) }} total • {{ number_format($balance->used_days, 1) }} used
                </div>
            </div>
        @endforeach
    </div>

    <!-- Request Leave Button -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Leave Requests</h2>
                <p class="text-sm text-gray-500">Manage your time off requests</p>
            </div>
            <button wire:click="openRequestModal" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                Request Leave
            </button>
        </div>
    </div>

    <!-- Leave Requests List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium">Your Leave Requests</h3>
        </div>
        
        @if($leaveRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($leaveRequests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ match($request->leave_type) {
                                            'vacation' => 'Vacation Leave',
                                            'sick' => 'Sick Leave',
                                            'personal' => 'Personal Leave',
                                            'bereavement' => 'Bereavement Leave',
                                            'maternity' => 'Maternity Leave',
                                            'paternity' => 'Paternity Leave',
                                            default => ucfirst(str_replace('_', ' ', $request->leave_type))
                                        } }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $request->start_date->format('M j, Y') }} - 
                                        {{ $request->end_date->format('M j, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $request->start_date->diffInDays($request->end_date) + 1 }} days
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ number_format($request->duration_days, 1) }} days
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($request->status === 'approved') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $request->approver?->name ?? '-' }}
                                    @if($request->approved_at)
                                        <div class="text-xs text-gray-500">
                                            {{ $request->approved_at->format('M j, Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-1">
                                        <button wire:click="openViewModal({{ $request->id }})" 
                                                class="text-blue-600 hover:text-blue-900"
                                                title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        
                                        @can('update', $request)
                                            <button wire:click="openEditModal({{ $request->id }})" 
                                                    class="text-green-600 hover:text-green-900"
                                                    title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                        @endcan

                                        @can('delete', $request)
                                            <button wire:click="deleteRequest({{ $request->id }})" 
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                    wire:confirm="Are you sure you want to delete this leave request?">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    {{ $leaveRequests->links() }}
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ $leaveRequests->firstItem() }}</span>
                            to
                            <span class="font-medium">{{ $leaveRequests->lastItem() }}</span>
                            of
                            <span class="font-medium">{{ $leaveRequests->total() }}</span>
                            results
                        </p>
                    </div>
                    <div>
                        {{ $leaveRequests->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No leave requests</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by requesting time off.</p>
                <div class="mt-6">
                    <button wire:click="openRequestModal" 
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Request Leave
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Request Leave Modal -->
    @if($showRequestModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Request Leave</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                            <select wire:model.live="leave_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Leave Type</option>
                                @foreach($leaveTypes as $type => $name)
                                    <option value="{{ $type }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('leave_type')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" 
                                       wire:model.live="start_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('start_date')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" 
                                       wire:model.live="end_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('end_date')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        @if($leave_type && $requestedDuration > 0)
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Requested Duration:</span>
                                    <span class="text-sm font-medium">{{ $requestedDuration }} days</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Available Balance:</span>
                                    <span class="text-sm font-medium {{ $availableBalance >= $requestedDuration ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $availableBalance }} days
                                    </span>
                                </div>
                                @if($availableBalance < $requestedDuration)
                                    <div class="text-red-500 text-xs mt-1">
                                        Insufficient leave balance
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <textarea wire:model="reason" 
                                      rows="4"
                                      placeholder="Please provide a reason for your leave request..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            @error('reason')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2 mt-6">
                        <button wire:click="$set('showRequestModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button wire:click="saveRequest" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                            Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Request Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Leave Request</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                            <select wire:model.live="leave_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Leave Type</option>
                                @foreach($leaveTypes as $type => $name)
                                    <option value="{{ $type }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('leave_type')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" 
                                       wire:model.live="start_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('start_date')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" 
                                       wire:model.live="end_date" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('end_date')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        @if($leave_type && $requestedDuration > 0)
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Requested Duration:</span>
                                    <span class="text-sm font-medium">{{ $requestedDuration }} days</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Available Balance:</span>
                                    <span class="text-sm font-medium {{ $availableBalance >= $requestedDuration ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $availableBalance }} days
                                    </span>
                                </div>
                            </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <textarea wire:model="reason" 
                                      rows="4"
                                      placeholder="Please provide a reason for your leave request..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            @error('reason')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2 mt-6">
                        <button wire:click="$set('showEditModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button wire:click="updateRequest" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                            Update Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- View Request Modal -->
    @if($showViewModal && $selectedRequest)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Leave Request Details</h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Leave Type:</span>
                                <div class="font-medium">
                                    @php
                                        echo match($selectedRequest->leave_type) {
                                            'vacation' => 'Vacation Leave',
                                            'sick' => 'Sick Leave',
                                            'personal' => 'Personal Leave',
                                            'bereavement' => 'Bereavement Leave',
                                            'maternity' => 'Maternity Leave',
                                            'paternity' => 'Paternity Leave',
                                            default => ucfirst(str_replace('_', ' ', $selectedRequest->leave_type))
                                        };
                                    @endphp
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Status:</span>
                                <div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($selectedRequest->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($selectedRequest->status === 'approved') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($selectedRequest->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Start Date:</span>
                                <div class="font-medium">{{ $selectedRequest->start_date->format('M j, Y') }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">End Date:</span>
                                <div class="font-medium">{{ $selectedRequest->end_date->format('M j, Y') }}</div>
                            </div>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-500">Duration:</span>
                            <div class="font-medium">{{ number_format($selectedRequest->duration_days, 1) }} days</div>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-500">Reason:</span>
                            <div class="font-medium">{{ $selectedRequest->reason }}</div>
                        </div>
                        
                        @if($selectedRequest->approved_by)
                            <div>
                                <span class="text-sm text-gray-500">Approved By:</span>
                                <div class="font-medium">{{ $selectedRequest->approver->name }}</div>
                                <div class="text-sm text-gray-500">{{ $selectedRequest->approved_at->format('M j, Y h:i A') }}</div>
                            </div>
                        @endif
                        
                        @if($selectedRequest->rejection_reason)
                            <div>
                                <span class="text-sm text-gray-500">Rejection Reason:</span>
                                <div class="font-medium text-red-600">{{ $selectedRequest->rejection_reason }}</div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button wire:click="$set('showViewModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
