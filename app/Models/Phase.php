<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'order',
        'weight',
        'progress',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'weight' => 'decimal:2',
        'progress' => 'decimal:2',
        'order' => 'integer',
    ];

    /**
     * Get the project this phase belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all tasks in this phase
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }

    /**
     * Calculate phase progress based on task completion
     */
    public function calculateProgress(): float
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks == 0) {
            return 0;
        }

        // Check if tasks have weights
        $totalWeight = $this->tasks->sum('weight');
        
        if ($totalWeight > 0) {
            // Weighted calculation
            $completedWeight = $this->tasks()
                ->where('status', 'completed')
                ->sum('weight');
            
            return round(($completedWeight / $totalWeight) * 100, 2);
        }

        // Simple count-based calculation
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Update phase progress and cascade to project
     */
    public function updateProgress(): void
    {
        $this->progress = $this->calculateProgress();
        $this->save();

        // Update project progress
        $this->project->updateProgress();
    }

    /**
     * Auto-update status based on progress
     */
    public function updateStatus(): void
    {
        if ($this->progress == 100) {
            $this->status = 'completed';
        } elseif ($this->progress > 0) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }
}
