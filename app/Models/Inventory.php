<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'project_id',
        'phase_id',
        'task_id',
        'quantity',
        'allocated_quantity',
        'used_quantity',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'allocated_quantity' => 'decimal:2',
        'used_quantity' => 'decimal:2',
    ];

    /**
     * Get the material
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the phase (optional)
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Get the task (optional)
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get remaining quantity (not allocated or used)
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity - $this->allocated_quantity - $this->used_quantity;
    }

    /**
     * Allocate material quantity
     */
    public function allocate(float $quantity): bool
    {
        if ($this->remaining_quantity >= $quantity) {
            $this->allocated_quantity += $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Use material quantity
     */
    public function use(float $quantity): bool
    {
        if ($this->allocated_quantity >= $quantity) {
            $this->allocated_quantity -= $quantity;
            $this->used_quantity += $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Update status based on quantities
     */
    public function updateStatus(): void
    {
        if ($this->remaining_quantity <= 0) {
            $this->status = 'depleted';
        } elseif ($this->allocated_quantity > 0) {
            $this->status = 'allocated';
        } else {
            $this->status = 'available';
        }
        $this->save();
    }
}
