<?php

namespace App\Livewire\TimeTracking;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveRequestForm extends Component
{
    use WithPagination;

    public $showRequestModal = false;
    public $showViewModal = false;
    public $showEditModal = false;
    public $selectedRequest = null;

    // Request form fields
    public $leave_type = '';
    public $start_date = '';
    public $end_date = '';
    public $reason = '';

    // Leave balances
    public $leaveBalances = [];
    public $availableBalance = 0;
    public $requestedDuration = 0;

    protected $listeners = ['refreshLeaveRequests' => '$refresh'];

    public function mount()
    {
        $this->loadLeaveBalances();
    }

    public function loadLeaveBalances()
    {
        /** @var \Illuminate\Support\Collection $balances */
        $this->leaveBalances = LeaveBalance::forUser(auth()->id())
            ->forYear(now()->year)
            ->get()
            ->keyBy('leave_type');
    }

    public function openRequestModal()
    {
        $this->authorize('create', LeaveRequest::class);
        $this->resetRequestForm();
        $this->showRequestModal = true;
    }

    public function openViewModal($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('view', $request);

        $this->selectedRequest = $request;
        $this->showViewModal = true;
    }

    public function openEditModal($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('update', $request);

        $this->selectedRequest = $request;
        $this->leave_type = $request->leave_type;
        $this->start_date = $request->start_date->format('Y-m-d');
        $this->end_date = $request->end_date->format('Y-m-d');
        $this->reason = $request->reason;

        $this->calculateDuration();
        $this->updateAvailableBalance();

        $this->showEditModal = true;
    }

    public function updatedLeaveType()
    {
        $this->updateAvailableBalance();
    }

    public function updatedStartDate()
    {
        $this->validate([
            'start_date' => 'required|date|after_or_equal:today',
        ]);

        $this->calculateDuration();
        $this->updateAvailableBalance();
    }

    public function updatedEndDate()
    {
        $this->validate([
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->calculateDuration();
        $this->updateAvailableBalance();
    }

    public function calculateDuration()
    {
        if (!$this->start_date || !$this->end_date) {
            $this->requestedDuration = 0;
            return;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        // Calculate business days (excluding weekends)
        $duration = 0;
        while ($start <= $end) {
            if (!$start->isWeekend()) {
                $duration++;
            }
            $start->addDay();
        }

        $this->requestedDuration = $duration;
    }

    public function updateAvailableBalance()
    {
        if (!$this->leave_type) {
            $this->availableBalance = 0;
            return;
        }

        $balance = $this->leaveBalances->get($this->leave_type);
        $this->availableBalance = $balance ? $balance->available_days : 0;
    }

    public function saveRequest()
    {
        $this->authorize('create', LeaveRequest::class);

        $this->validate([
            'leave_type' => 'required|in:' . implode(',', array_keys(LeaveBalance::getLeaveTypes())),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
        ]);

        // Check for overlapping leave requests
        $existingRequest = LeaveRequest::forUser(auth()->id())
            ->pending()
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            })
            ->first();

        if ($existingRequest) {
            $this->addError('start_date', 'You already have a pending leave request that overlaps with these dates.');
            return;
        }

        // Check leave balance
        if ($this->requestedDuration > $this->availableBalance) {
            $this->addError('leave_type', "Insufficient leave balance. Available: {$this->availableBalance} days, Requested: {$this->requestedDuration} days");
            return;
        }

        LeaveRequest::create([
            'user_id' => auth()->id(),
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reason' => $this->reason,
            'status' => 'pending',
            'duration_days' => $this->requestedDuration,
        ]);

        $this->showRequestModal = false;
        $this->resetRequestForm();
        $this->loadLeaveBalances();

        Flux::toast('Leave request submitted successfully', variant: 'success');
        $this->dispatch('refreshLeaveRequests');
    }

    public function updateRequest()
    {
        if (!$this->selectedRequest) {
            return;
        }

        $this->authorize('update', $this->selectedRequest);

        $this->validate([
            'leave_type' => 'required|in:' . implode(',', array_keys(LeaveBalance::getLeaveTypes())),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
        ]);

        // Check leave balance for updated request
        if ($this->requestedDuration > $this->availableBalance) {
            $this->addError('leave_type', "Insufficient leave balance. Available: {$this->availableBalance} days, Requested: {$this->requestedDuration} days");
            return;
        }

        $this->selectedRequest->update([
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reason' => $this->reason,
            'duration_days' => $this->requestedDuration,
        ]);

        $this->showEditModal = false;
        $this->resetRequestForm();

        Flux::toast('Leave request updated successfully', variant: 'success');
        $this->dispatch('refreshLeaveRequests');
    }

    public function deleteRequest($requestId)
    {
        $request = LeaveRequest::findOrFail($requestId);
        $this->authorize('delete', $request);

        $request->delete();

        Flux::toast('Leave request deleted successfully', variant: 'success');
        $this->dispatch('refreshLeaveRequests');
    }

    public function getLeaveRequests()
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $requests */
        $requests = LeaveRequest::forUser(auth()->id())
            ->with(['approver'])
            ->latest()
            ->paginate(10);

        return $requests;
    }

    public function getLeaveTypes()
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

    public function canEditRequest($request)
    {
        return $request->isPending() && $request->user_id === auth()->id();
    }

    public function canDeleteRequest($request)
    {
        return $request->isPending() && $request->user_id === auth()->id();
    }

    private function resetRequestForm()
    {
        $this->reset([
            'leave_type',
            'start_date',
            'end_date',
            'reason',
            'availableBalance',
            'requestedDuration'
        ]);
        $this->selectedRequest = null;
    }

    public function render()
    {
        return view('livewire.time-tracking.leave-request-form', [
            'leaveRequests' => $this->getLeaveRequests(),
            'leaveTypes' => $this->getLeaveTypes(),
        ]);
    }
}
