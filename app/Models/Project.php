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
        'client_id',
        'location',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_budget',
        'actual_cost',
        'bill_of_quantities_total',
        'currency',
        'overall_progress',
        'manager_id',
        'inspector_id',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'estimated_budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'bill_of_quantities_total' => 'decimal:2',
        'overall_progress' => 'decimal:2',
    ];

    /**
     * Get the project manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('order');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function billOfQuantities(): HasMany
    {
        return $this->hasMany(BillOfQuantity::class)->orderBy('order');
    }

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

    public function updateProgress(): void
    {
        $this->overall_progress = $this->calculateProgress();
        $this->save();
    }

    public function getBudgetVarianceAttribute(): float
    {
        if (!$this->estimated_budget) {
            return 0;
        }

        return $this->estimated_budget - $this->actual_cost;
    }

    public function getBudgetVariancePercentageAttribute(): float
    {
        if (!$this->estimated_budget || $this->estimated_budget == 0) {
            return 0;
        }

        return (($this->estimated_budget - $this->actual_cost) / $this->estimated_budget) * 100;
    }

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

    public function calculateTotalTaskCosts(): array
    {
        $estimatedCost = 0;
        $actualCost = 0;

        foreach ($this->phases as $phase) {
            foreach ($phase->tasks as $task) {
                $estimatedCost += $task->estimated_cost ?? 0;
                $actualCost += $task->actual_cost ?? 0;
            }
        }

        return [
            'estimated' => $estimatedCost,
            'actual' => $actualCost,
            'variance' => $estimatedCost - $actualCost,
        ];
    }

    public function updateActualCostFromTasks(): void
    {
        $costs = $this->calculateTotalTaskCosts();
        $this->actual_cost = $costs['actual'];
        $this->save();
    }

    public function getBudgetUtilizationAttribute(): float
    {
        if (!$this->estimated_budget || $this->estimated_budget == 0) {
            return 0;
        }

        return ($this->actual_cost / $this->estimated_budget) * 100;
    }

    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->currency) {
            'NGN' => '₦',
            default => '$',
        };
    }

    public function formatCurrency(null|float|string $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0;
        $sign = $value < 0 ? '-' : '';

        return $sign . $this->currency_symbol . number_format(abs($value), $decimals);
    }
}
