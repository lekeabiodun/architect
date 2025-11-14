<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'break_duration',
        'notes',
        'project_id',
        'task_id',
        'location',
        'overtime_hours',
        'edited_by',
        'edit_reason',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_duration' => 'integer',
        'overtime_hours' => 'decimal:2',
    ];

    /**
     * Get the user that owns the time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task associated with the time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who edited this time entry.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Check if the time entry is currently active (clocked in but not clocked out).
     */
    public function isActive(): bool
    {
        return $this->clock_in && !$this->clock_out;
    }

    /**
     * Calculate the total hours worked for this entry.
     */
    public function getTotalHoursAttribute(): float
    {
        if (!$this->clock_in) {
            return 0;
        }

        $clockOut = $this->clock_out ?: now();
        $totalMinutes = $this->clock_in->diffInMinutes($clockOut);
        $breakMinutes = $this->break_duration ?? 0;

        return max(0, ($totalMinutes - $breakMinutes) / 60);
    }

    /**
     * Calculate the regular hours worked (excluding overtime).
     */
    public function getRegularHoursAttribute(): float
    {
        $totalHours = $this->total_hours;
        $overtimeHours = $this->overtime_hours ?? 0;

        return max(0, $totalHours - $overtimeHours);
    }

    /**
     * Clock out the current time entry.
     */
    public function clockOut(?Carbon $clockOutTime = null): void
    {
        $this->clock_out = $clockOutTime ?: now();

        // Calculate overtime if more than 8 hours
        $totalHours = $this->total_hours;
        $this->overtime_hours = max(0, $totalHours - 8);

        $this->save();
    }

    /**
     * Update break duration.
     */
    public function updateBreakDuration(int $minutes): void
    {
        $this->break_duration = max(0, $minutes);
        $this->save();
    }

    /**
     * Scope a query to only include active time entries.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('clock_out');
    }

    /**
     * Scope a query to only include entries for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('clock_in', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include entries for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the current active time entry for a user.
     */
    public static function getActiveForUser($userId): ?self
    {
        return static::forUser($userId)->active()->first();
    }
}
