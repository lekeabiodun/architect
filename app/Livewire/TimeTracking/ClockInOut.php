<?php

namespace App\Livewire\TimeTracking;

use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\Task;
use Livewire\Component;
use Flux\Flux;
use Carbon\Carbon;

class ClockInOut extends Component
{
    public $activeEntry;
    public $showBreakModal = false;
    public $breakDuration = 0;
    public $notes = '';
    public $selectedProject = '';
    public $selectedTask = '';
    public $location = '';

    // Today's summary
    public $todayHours = 0;
    public $weekHours = 0;
    public $monthHours = 0;
    public $recentEntries;

    protected $listeners = ['refreshTimeTracking' => '$refresh'];

    public function mount()
    {
        $this->loadActiveEntry();
        $this->loadSummary();
        $this->loadRecentEntries();
    }

    public function loadActiveEntry()
    {
        $this->activeEntry = TimeEntry::getActiveForUser(auth()->id());

        if ($this->activeEntry) {
            $this->notes = $this->activeEntry->notes;
            $this->selectedProject = $this->activeEntry->project_id;
            $this->selectedTask = $this->activeEntry->task_id;
            $this->location = $this->activeEntry->location;
        }
    }

    public function loadSummary()
    {
        $user = auth()->user();
        $this->todayHours = $user->today_hours;
        $this->weekHours = $user->week_hours;
        $this->monthHours = $user->month_hours;
    }

    public function loadRecentEntries()
    {
        $this->recentEntries = TimeEntry::forUser(auth()->id())
            ->with(['project', 'task'])
            ->latest()
            ->limit(5)
            ->get();
    }

    public function clockIn()
    {
        $this->authorize('clockIn', TimeEntry::class);

        // Check if user already has an active entry
        if (TimeEntry::getActiveForUser(auth()->id())) {
            Flux::toast('You already have an active time entry. Please clock out first.', variant: 'warning');
            return;
        }

        $entry = TimeEntry::create([
            'user_id' => auth()->id(),
            'clock_in' => now(),
            'notes' => $this->notes,
            'project_id' => $this->selectedProject ?: null,
            'task_id' => $this->selectedTask ?: null,
            'location' => $this->location ?: $this->getCurrentLocation(),
        ]);

        $this->activeEntry = $entry;
        $this->reset(['notes', 'selectedProject', 'selectedTask', 'location']);

        Flux::toast('Successfully clocked in at ' . $entry->clock_in->format('h:i A'), variant: 'success');
        $this->dispatch('refreshTimeTracking');
    }

    public function clockOut()
    {
        if (!$this->activeEntry) {
            Flux::toast('No active time entry found.', variant: 'danger');
            return;
        }

        $this->authorize('clockOut', $this->activeEntry);

        // Update notes before clocking out
        $this->activeEntry->update([
            'notes' => $this->notes,
            'project_id' => $this->selectedProject ?: null,
            'task_id' => $this->selectedTask ?: null,
            'location' => $this->location ?: $this->activeEntry->location,
        ]);

        $this->activeEntry->clockOut();

        $clockOutTime = $this->activeEntry->clock_out->format('h:i A');
        $totalHours = number_format($this->activeEntry->total_hours, 2);

        $this->activeEntry = null;
        $this->reset(['notes', 'selectedProject', 'selectedTask', 'location']);

        Flux::toast("Successfully clocked out at {$clockOutTime}. Total hours: {$totalHours}", variant: 'success');
        $this->dispatch('refreshTimeTracking');

        // Reload summary and recent entries
        $this->loadSummary();
        $this->loadRecentEntries();
    }

    public function openBreakModal()
    {
        if (!$this->activeEntry) {
            Flux::toast('No active time entry found.', variant: 'danger');
            return;
        }

        $this->breakDuration = $this->activeEntry->break_duration;
        $this->showBreakModal = true;
    }

    public function updateBreakDuration()
    {
        if (!$this->activeEntry) {
            return;
        }

        $this->validate([
            'breakDuration' => 'required|integer|min:0|max:480',
        ]);

        $this->activeEntry->updateBreakDuration($this->breakDuration);
        $this->showBreakModal = false;

        Flux::toast("Break duration updated to {$this->breakDuration} minutes", variant: 'success');
    }

    public function getCurrentLocation(): string
    {
        // In a real implementation, you might use IP-based geolocation
        // or browser geolocation API. For now, return a placeholder.
        return 'Office';
    }

    public function getProjectsProperty()
    {
        return auth()->user()->getAccessibleProjects()->pluck('name', 'id');
    }

    public function getTasksProperty()
    {
        if (!$this->selectedProject) {
            return collect();
        }

        return Task::whereHas('phase', function ($query) {
            $query->where('project_id', $this->selectedProject);
        })
            ->where('assigned_to', auth()->id())
            ->pluck('name', 'id');
    }

    public function getActiveTimeProperty()
    {
        if (!$this->activeEntry || !$this->activeEntry->clock_in) {
            return '00:00:00';
        }

        try {
            $diff = $this->activeEntry->clock_in->diff(now());
            return $diff->format('%H:%i:%S');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    public function getEstimatedHoursProperty()
    {
        if (!$this->activeEntry) {
            return 0;
        }

        $totalMinutes = $this->activeEntry->clock_in->diffInMinutes(now());
        $breakMinutes = $this->activeEntry->break_duration ?? 0;

        return max(0, ($totalMinutes - $breakMinutes) / 60);
    }

    public function render()
    {
        return view('livewire.time-tracking.clock-in-out', [
            'activeTime' => $this->getActiveTimeProperty(),
            'estimatedHours' => $this->getEstimatedHoursProperty(),
            'projects' => $this->projects,
            'tasks' => $this->tasks,
        ]);
    }
}
