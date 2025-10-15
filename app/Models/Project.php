<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'client_name',
        'location',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_budget',
        'actual_cost',
        'overall_progress',
        'manager_id',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'estimated_budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'overall_progress' => 'decimal:2',
    ];

    /**
     * Get the project manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all phases for this project
     */
    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('order');
    }

    /**
     * Get all team members assigned to this project
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Get all documents attached to this project
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Calculate overall project progress based on phase weights
     */
    public function calculateProgress(): float
    {
        $totalWeight = $this->phases->sum('weight');
        
        if ($totalWeight == 0) {
            return 0;
        }

        $weightedProgress = $this->phases->sum(function ($phase) {
            return ($phase->progress * $phase->weight) / 100;
        });

        return round($weightedProgress, 2);
    }

    /**
     * Update overall progress and save
     */
    public function updateProgress(): void
    {
        $this->overall_progress = $this->calculateProgress();
        $this->save();
    }

    /**
     * Get budget variance (positive means under budget)
     */
    public function getBudgetVarianceAttribute(): float
    {
        if (!$this->estimated_budget) {
            return 0;
        }
        
        return $this->estimated_budget - $this->actual_cost;
    }

    /**
     * Get budget variance percentage
     */
    public function getBudgetVariancePercentageAttribute(): float
    {
        if (!$this->estimated_budget || $this->estimated_budget == 0) {
            return 0;
        }
        
        return (($this->estimated_budget - $this->actual_cost) / $this->estimated_budget) * 100;
    }

    /**
     * Check if project is on schedule
     */
    public function isOnSchedule(): bool
    {
        if (!$this->planned_end_date) {
            return true;
        }

        if ($this->status === 'completed') {
            return !$this->actual_end_date || $this->actual_end_date <= $this->planned_end_date;
        }

        return now() <= $this->planned_end_date;
    }
}
