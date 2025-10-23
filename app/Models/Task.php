<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_id',
        'name',
        'description',
        'status',
        'order',
        'weight',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_cost',
        'actual_cost',
        'estimated_hours',
        'actual_hours',
        'predecessor_task_id',
        'assigned_to',
        'inspection_status',
        'inspection_notes',
        'inspected_by',
        'inspected_at',
        'inspector_feedback',
        'requires_re_inspection',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'weight' => 'decimal:2',
        'order' => 'integer',
        'inspected_at' => 'datetime',
        'requires_re_inspection' => 'boolean',
    ];

    /**
     * Get the phase this task belongs to
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Get the user assigned to this task
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the predecessor task (dependency)
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_task_id');
    }

    /**
     * Get tasks that depend on this task
     */
    public function successors(): HasMany
    {
        return $this->hasMany(Task::class, 'predecessor_task_id');
    }

    /**
     * Get all comments for this task
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * Get all documents attached to this task
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Get the inspector who inspected this task
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Get all inventories for this task
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get all material requests for this task
     */
    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    /**
     * Toggle task status (for circle click)
     */
    public function toggleStatus(): void
    {
        $this->status = match ($this->status) {
            'pending' => 'in_progress',
            'in_progress' => 'completed',
            'completed' => 'pending',
            default => 'pending',
        };

        if ($this->status === 'completed') {
            $this->actual_end_date = now();
        } elseif ($this->status === 'in_progress' && !$this->actual_start_date) {
            $this->actual_start_date = now();
        }

        $this->save();

        // Update phase and project progress
        $this->phase->updateProgress();
        $this->phase->updateStatus();
    }

    /**
     * Check if task can be started based on dependencies
     */
    public function canStart(): bool
    {
        if (!$this->predecessor_task_id) {
            return true;
        }

        return $this->predecessor?->status === 'completed';
    }

    /**
     * Get cost variance
     */
    public function getCostVarianceAttribute(): float
    {
        if (!$this->estimated_cost) {
            return 0;
        }

        return $this->estimated_cost - $this->actual_cost;
    }

    /**
     * Approve task inspection
     */
    public function approveInspection(User $inspector, ?string $feedback = null): void
    {
        $this->inspection_status = 'passed';
        $this->inspected_by = $inspector->id;
        $this->inspected_at = now();
        $this->inspector_feedback = $feedback;
        $this->requires_re_inspection = false;
        $this->save();
    }

    /**
     * Fail task inspection
     */
    public function failInspection(User $inspector, string $feedback, bool $requiresReInspection = true): void
    {
        $this->inspection_status = 'failed';
        $this->inspected_by = $inspector->id;
        $this->inspected_at = now();
        $this->inspector_feedback = $feedback;
        $this->requires_re_inspection = $requiresReInspection;
        $this->save();
    }

    /**
     * Request re-inspection
     */
    public function requestReInspection(): void
    {
        $this->inspection_status = 're_inspection';
        $this->requires_re_inspection = true;
        $this->save();
    }

    /**
     * Check if task requires inspection
     */
    public function requiresInspection(): bool
    {
        return $this->status === 'completed' &&
            in_array($this->inspection_status, ['pending', 're_inspection', null]);
    }

    /**
     * Check if task is fully approved
     */
    public function isFullyApproved(): bool
    {
        return $this->status === 'completed' &&
            $this->inspection_status === 'passed';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
