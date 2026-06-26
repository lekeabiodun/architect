<?php

namespace App\Livewire\TimeTracking;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class AdminTimesheet extends Component
{
    use WithPagination;

    public $showEditModal = false;

    public $showDeleteModal = false;

    public $selectedEntry = null;

    // Filters
    public $user_filter = '';

    public $project_filter = '';

    public $status_filter = '';

    public $date_range_filter = 'week';

    public $custom_start_date = '';

    public $custom_end_date = '';

    // Edit form
    public $edit_clock_in = '';

    public $edit_clock_out = '';

    public $edit_break_duration = '';

    public $edit_notes = '';

    public $edit_project_id = '';

    public $edit_task_id = '';

    public $edit_reason = '';

    // Summary data
    public $totalHours = 0;

    public $totalRegularHours = 0;

    public $totalOvertimeHours = 0;

    public $averageDailyHours = 0;

    public $activeUsersCount = 0;

    protected $queryString = ['user_filter', 'project_filter', 'status_filter', 'date_range_filter'];

    public function mount()
    {
        $this->authorize('viewAny', TimeEntry::class);

        $this->custom_start_date = now()->startOfWeek()->format('Y-m-d');
        $this->custom_end_date = now()->endOfWeek()->format('Y-m-d');
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

    public function getTimeEntries()
    {
        $query = TimeEntry::query()
            ->with(['user', 'project', 'task', 'editor'])
            ->dateRange($this->custom_start_date, $this->custom_end_date);

        // Filter by user
        if ($this->user_filter) {
            $query->where('user_id', $this->user_filter);
        }

        // Filter by project
        if ($this->project_filter) {
            $query->where('project_id', $this->project_filter);
        }

        // Filter by status
        if ($this->status_filter === 'active') {
            $query->active();
        } elseif ($this->status_filter === 'completed') {
            $query->whereNotNull('clock_out');
        }

        return $query->latest('clock_in')->paginate(20);
    }

    public function loadSummary()
    {
        $query = TimeEntry::query()
            ->dateRange($this->custom_start_date, $this->custom_end_date);

        // Apply same filters as main query
        if ($this->user_filter) {
            $query->where('user_id', $this->user_filter);
        }
        if ($this->project_filter) {
            $query->where('project_id', $this->project_filter);
        }

        $entries = $query->get();

        $this->totalHours = $entries->sum('total_hours');
        $this->totalRegularHours = $entries->sum('regular_hours');
        $this->totalOvertimeHours = $entries->sum('overtime_hours');

        $days = $this->getDateRangeDays();
        $this->averageDailyHours = $days > 0 ? $this->totalHours / $days : 0;

        $this->activeUsersCount = $entries->pluck('user_id')->unique()->count();
    }

    public function getDateRangeDays(): int
    {
        $start = Carbon::parse($this->custom_start_date);
        $end = Carbon::parse($this->custom_end_date);

        return $start->diffInDays($end) + 1;
    }

    public function openEditModal($entryId)
    {
        $this->authorize('editTimeEntry', TimeEntry::class);

        $this->selectedEntry = TimeEntry::findOrFail($entryId);
        $this->edit_clock_in = $this->selectedEntry->clock_in->format('Y-m-d\TH:i');
        $this->edit_clock_out = $this->selectedEntry->clock_out?->format('Y-m-d\TH:i');
        $this->edit_break_duration = $this->selectedEntry->break_duration;
        $this->edit_notes = $this->selectedEntry->notes;
        $this->edit_project_id = $this->selectedEntry->project_id;
        $this->edit_task_id = $this->selectedEntry->task_id;
        $this->edit_reason = '';

        $this->showEditModal = true;
    }

    public function updateTimeEntry()
    {
        $this->authorize('editTimeEntry', TimeEntry::class);

        $this->validate([
            'edit_clock_in' => 'required|date',
            'edit_clock_out' => 'nullable|date|after:edit_clock_in',
            'edit_break_duration' => 'required|integer|min:0',
            'edit_reason' => 'required|string|min:5',
        ]);

        $clockIn = Carbon::parse($this->edit_clock_in);
        $clockOut = $this->edit_clock_out ? Carbon::parse($this->edit_clock_out) : null;

        $this->selectedEntry->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_duration' => $this->edit_break_duration,
            'notes' => $this->edit_notes,
            'project_id' => $this->edit_project_id ?: null,
            'task_id' => $this->edit_task_id ?: null,
            'edited_by' => auth()->id(),
            'edit_reason' => $this->edit_reason,
        ]);

        // Recalculate overtime if clocked out
        if ($clockOut) {
            $totalHours = $clockIn->diffInMinutes($clockOut) / 60;
            $breakHours = $this->edit_break_duration / 60;
            $workedHours = max(0, $totalHours - $breakHours);
            $overtimeHours = max(0, $workedHours - 8);

            $this->selectedEntry->update([
                'overtime_hours' => $overtimeHours,
            ]);
        }

        $this->showEditModal = false;
        $this->resetEditForm();

        Flux::toast('Time entry updated successfully', variant: 'success');
    }

    public function openDeleteModal($entryId)
    {
        $this->selectedEntry = TimeEntry::findOrFail($entryId);
        $this->showDeleteModal = true;
    }

    public function deleteTimeEntry()
    {
        $this->authorize('delete', $this->selectedEntry);

        $this->selectedEntry->delete();

        $this->showDeleteModal = false;
        $this->selectedEntry = null;

        Flux::toast('Time entry deleted successfully', variant: 'success');
    }

    public function exportToCsv()
    {
        $entries = TimeEntry::query()
            ->with(['user', 'project', 'task'])
            ->dateRange($this->custom_start_date, $this->custom_end_date)
            ->when($this->user_filter, fn ($q) => $q->where('user_id', $this->user_filter))
            ->when($this->project_filter, fn ($q) => $q->where('project_id', $this->project_filter))
            ->when($this->status_filter === 'active', fn ($q) => $q->active())
            ->when($this->status_filter === 'completed', fn ($q) => $q->whereNotNull('clock_out'))
            ->latest('clock_in')
            ->get();

        $csvData = [];
        $csvData[] = ['Date', 'User', 'Clock In', 'Clock Out', 'Duration', 'Break', 'Regular Hours', 'Overtime', 'Project', 'Task', 'Notes'];

        foreach ($entries as $entry) {
            $csvData[] = [
                $entry->clock_in->format('Y-m-d'),
                $entry->user->name,
                $entry->clock_in->format('H:i'),
                $entry->clock_out?->format('H:i') ?? '',
                number_format($entry->total_hours, 2),
                $entry->break_duration,
                number_format($entry->regular_hours, 2),
                number_format($entry->overtime_hours, 2),
                $entry->project?->name ?? '',
                $entry->task?->name ?? '',
                $entry->notes ?? '',
            ];
        }

        $filename = 'timesheet_'.$this->custom_start_date.'_to_'.$this->custom_end_date.'.csv';

        $this->dispatch('downloadCsv', [
            'filename' => $filename,
            'data' => $csvData,
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['user_filter', 'project_filter', 'status_filter']);
        $this->date_range_filter = 'week';
        $this->updateDateRange();
    }

    private function resetEditForm()
    {
        $this->reset([
            'edit_clock_in',
            'edit_clock_out',
            'edit_break_duration',
            'edit_notes',
            'edit_project_id',
            'edit_task_id',
            'edit_reason',
        ]);
        $this->selectedEntry = null;
    }

    public function getUsersProperty()
    {
        return User::whereHas('timeEntries', function ($query) {
            $query->dateRange($this->custom_start_date, $this->custom_end_date);
        })->orderBy('name')->pluck('name', 'id');
    }

    public function getProjectsProperty()
    {
        return Project::whereHas('timeEntries', function ($query) {
            $query->dateRange($this->custom_start_date, $this->custom_end_date);
        })->orderBy('name')->pluck('name', 'id');
    }

    public function render()
    {
        $this->loadSummary();

        return view('livewire.time-tracking.admin-timesheet', [
            'timeEntries' => $this->getTimeEntries(),
            'users' => $this->users,
            'projects' => $this->projects,
        ]);
    }
}
