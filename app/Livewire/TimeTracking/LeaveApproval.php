<?php

namespace App\Livewire\TimeTracking;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\LeaveBalance;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Carbon\Carbon;

class LeaveApproval extends Component
{
    use WithPagination;

    public $showApproveModal = false;
    public $showRejectModal = false;
    public $showViewModal = false;
    public $selectedRequest = null;

    // Filters
    public $status_filter = 'pending';
    public $user_filter = '';
    public $leave_type_filter = '';
    public $date_range_filter = 'month';
    public $custom_start_date = '';
    public $custom_end_date = '';

    // Approval/Rejection fields
    public $approval_notes = '';
    public $rejection_reason = '';

    // Summary data
    public $pendingCount = 0;
    public $approvedCount = 0;
    public $rejectedCount = 0;

    protected $queryString = ['status_filter', 'user_filter', 'leave_type_filter', 'date_range_filter'];

    public function mount()
    {
        $this->custom_start_date = now()->startOfMonth()->format('Y-m-d');
        $this->custom_end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedDateRangeFilter()
    {
        $this->updateDateRange();
    }

    public function updateDateRange()
    {
        switch ($this->date_range_filter) {
            case 'today':
                $this->custom_start_date = now()->format('Y-m-d');
                $this->custom_end_date = now()->format('Y-m-d');
                break;
            case 'week':
                $this->custom_start_date = now()->startOfWeek()->format('Y-m-d');
                $this->custom_end_date = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->custom_start_date = now()->startOfMonth()->format('Y-m-d');
                $this->custom_end_date = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'year':
                $this->custom_start_date = now()->startOfYear()->format('Y-m-d');
                $this->custom_end_date = now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getLeaveRequests()
    {
        $query = LeaveRequest::query()
            ->with(['user', 'approver'])
            ->dateRange($this->custom_start_date, $this->custom_end_date);

        // Filter by status
        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        // Filter by user
        if ($this->user_filter) {
            $query->where('user_id', $this->user_filter);
        }

        // Filter by leave type
        if ($this->leave_type_filter) {
            $query->where('leave_type', $this->leave_type_filter);
        }

        return $query->latest()->paginate(15);
    }

    public function loadSummary()
    {
        $this->pendingCount = LeaveRequest::pending()->count();
        $this->approvedCount = LeaveRequest::approved()->count();
        $this->rejectedCount = LeaveRequest::rejected()->count();
    }

    public function openViewModal($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('view', $request);

        $this->selectedRequest = $request;
        $this->showViewModal = true;
    }

    public function openApproveModal($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('approve', $request);

        $this->selectedRequest = $request;
        $this->approval_notes = '';
        $this->showApproveModal = true;
    }

    public function openRejectModal($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('reject', $request);

        $this->selectedRequest = $request;
        $this->rejection_reason = '';
        $this->showRejectModal = true;
    }

    public function approveRequest()
    {
        if (!$this->selectedRequest) {
            return;
        }

        $this->authorize('approve', $this->selectedRequest);

        $this->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        // Check if user has sufficient balance
        if (!$this->selectedRequest->hasSufficientBalance()) {
            $this->addError('approval_notes', 'User does not have sufficient leave balance for this request.');
            return;
        }

        $this->selectedRequest->approve(auth()->user());

        if ($this->approval_notes) {
            $this->selectedRequest->update([
                'notes' => $this->approval_notes,
                'edited_by' => auth()->id(),
                'edit_reason' => 'Approval notes added',
            ]);
        }

        $this->showApproveModal = false;
        $this->selectedRequest = null;
        $this->approval_notes = '';

        Flux::toast('Leave request approved successfully', variant: 'success');
    }

    public function rejectRequest()
    {
        if (!$this->selectedRequest) {
            return;
        }

        $this->authorize('reject', $this->selectedRequest);

        $this->validate([
            'rejection_reason' => 'required|string|min:5|max:500',
        ]);

        $this->selectedRequest->reject(auth()->user(), $this->rejection_reason);

        $this->showRejectModal = false;
        $this->selectedRequest = null;
        $this->rejection_reason = '';

        Flux::toast('Leave request rejected', variant: 'warning');
    }

    public function bulkApprove()
    {
        $this->authorize('approve', LeaveRequest::class);

        $requests = LeaveRequest::pending()
            ->dateRange($this->custom_start_date, $this->custom_end_date)
            ->when($this->user_filter, fn($q) => $q->where('user_id', $this->user_filter))
            ->when($this->leave_type_filter, fn($q) => $q->where('leave_type', $this->leave_type_filter))
            ->get();

        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($requests as $request) {
            if ($request->hasSufficientBalance()) {
                $request->approve(auth()->user());
                $approvedCount++;
            } else {
                $request->reject(auth()->user(), 'Insufficient leave balance');
                $rejectedCount++;
            }
        }

        $message = "Bulk processed: {$approvedCount} approved, {$rejectedCount} rejected";
        Flux::toast($message, variant: 'success');
    }

    public function exportToCsv()
    {
        $requests = LeaveRequest::query()
            ->with(['user', 'approver'])
            ->dateRange($this->custom_start_date, $this->custom_end_date)
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->user_filter, fn($q) => $q->where('user_id', $this->user_filter))
            ->when($this->leave_type_filter, fn($q) => $q->where('leave_type', $this->leave_type_filter))
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = ['Date', 'User', 'Email', 'Leave Type', 'Start Date', 'End Date', 'Duration', 'Status', 'Approved By', 'Reason'];

        foreach ($requests as $request) {
            $csvData[] = [
                $request->created_at->format('Y-m-d'),
                $request->user->name,
                $request->user->email,
                $this->getLeaveTypeName($request->leave_type),
                $request->start_date->format('Y-m-d'),
                $request->end_date->format('Y-m-d'),
                number_format($request->duration_days, 1),
                ucfirst($request->status),
                $request->approver?->name ?? '',
                $request->reason ?? '',
            ];
        }

        $filename = 'leave_requests_' . $this->custom_start_date . '_to_' . $this->custom_end_date . '.csv';

        $this->dispatch('downloadCsv', [
            'filename' => $filename,
            'data' => $csvData,
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['user_filter', 'leave_type_filter']);
        $this->status_filter = 'pending';
        $this->date_range_filter = 'month';
        $this->updateDateRange();
    }

    public function getUsersProperty()
    {
        return User::whereHas('leaveRequests')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function getLeaveTypesProperty()
    {
        return LeaveBalance::getLeaveTypes();
    }

    public function getLeaveTypeName($type)
    {
        return $this->getLeaveTypes()[$type] ?? ucfirst($type);
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function render()
    {
        $this->loadSummary();

        return view('livewire.time-tracking.leave-approval', [
            'leaveRequests' => $this->getLeaveRequests(),
            'users' => $this->users,
            'leaveTypes' => $this->leaveTypes,
        ]);
    }
}
