<div>
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">Pending Requests</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">Approved Requests</div>
            <div class="text-2xl font-bold text-green-600">{{ $approvedCount }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">Rejected Requests</div>
            <div class="text-2xl font-bold text-red-600">{{ $rejectedCount }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Leave Request Management</h2>
            <div class="flex space-x-2">
                <button wire:click="bulkApprove" 
                        class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm">
                    Bulk Approve
                </button>
                <button wire:click="exportToCsv" 
                        class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition text-sm">
                    Export CSV
                </button>
                <button wire:click="resetFilters" 
                        class="px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition text-sm">
                    Reset
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="status_filter" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <select wire:model.live="date_range_filter" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            
            @if($date_range_filter === 'custom')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" 
                           wire:model.live="custom_start_date" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" 
                           wire:model.live="custom_end_date" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            @endif
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select wire:model.live="user_filter" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Users</option>
                    @foreach($users as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                <select wire:model.live="leave_type_filter" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $type => $name)
                        <option value="{{ $type }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium">Leave Requests</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaveRequests as $request)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-medium">
                                            {{ $request->user->initials() }}
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $request->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    @php
                                        echo match($request->leave_type) {
                                            'vacation' => 'Vacation Leave',
                                            'sick' => 'Sick Leave',
                                            'personal' => 'Personal Leave',
                                            'bereavement' => 'Bereavement Leave',
                                            'maternity' => 'Maternity Leave',
                                            'paternity' => 'Paternity Leave',
                                            default => ucfirst(str_replace('_', ' ', $request->leave_type))
                                        };
                                    @endphp
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
                                <div class="text-sm text-gray-900">{{ number_format($request->duration_days, 1) }} days</div>
                                <div class="text-xs text-gray-500">
                                    Balance: {{ number_format($request->available_balance, 1) }} days
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
                                {{ $request->created_at->format('M j, Y') }}
                                <div class="text-xs text-gray-500">
                                    {{ $request->created_at->format('h:i A') }}
                                </div>
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
                                    
                                    @if($request->isPending())
                                        <button wire:click="openApproveModal({{ $request->id }})" 
                                                class="text-green-600 hover:text-green-900"
                                                title="Approve">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                        
                                        <button wire:click="openRejectModal({{ $request->id }})" 
                                                class="text-red-600 hover:text-red-900"
                                                title="Reject">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No leave requests found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
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
    </div>

    <!-- View Request Modal -->
    @if($showViewModal && $selectedRequest)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Leave Request Details</h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Employee:</span>
                                <div class="font-medium">{{ $selectedRequest->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $selectedRequest->user->email }}</div>
                            </div>
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
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Duration:</span>
                                <div class="font-medium">{{ number_format($selectedRequest->duration_days, 1) }} days</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Available Balance:</span>
                                <div class="font-medium {{ $selectedRequest->available_balance >= $selectedRequest->duration_days ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($selectedRequest->available_balance, 1) }} days
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-500">Reason:</span>
                            <div class="font-medium">{{ $selectedRequest->reason }}</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500">Requested:</span>
                                <div class="font-medium">{{ $selectedRequest->created_at->format('M j, Y h:i A') }}</div>
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

    <!-- Approve Modal -->
    @if($showApproveModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Approve Leave Request</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Are you sure you want to approve this leave request?
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Approval Notes (optional)</label>
                        <textarea wire:model="approval_notes" 
                                  rows="3"
                                  placeholder="Add any notes for the approval..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-center space-x-2">
                        <button wire:click="$set('showApproveModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button wire:click="approveRequest" 
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition">
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Reject Modal -->
    @if($showRejectModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Reject Leave Request</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Please provide a reason for rejecting this leave request.
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea wire:model="rejection_reason" 
                                  rows="3"
                                  placeholder="Enter the reason for rejection..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('rejection_reason')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex justify-center space-x-2">
                        <button wire:click="$set('showRejectModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button wire:click="rejectRequest" 
                                class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition">
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if(session()->has('message'))
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50">
            {{ session('message') }}
        </div>
    @endif

    <!-- CSV Download Script -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('downloadCsv', ({ filename, data }) => {
                let csvContent = data.map(row => row.join(',')).join('\n');
                let blob = new Blob([csvContent], { type: 'text/csv' });
                let url = window.URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.click();
                window.URL.revokeObjectURL(url);
            });
        });
    </script>
</div>
