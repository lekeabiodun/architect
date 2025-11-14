<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase_id',
        'task_id',
        'bill_of_quantity_id',
        'requested_quantity',
        'approved_quantity',
        'disbursed_quantity',
        'confirmed_quantity',
        'required_date',
        'purpose',
        'justification',
        'requested_by',
        'approved_by',
        'disbursed_by',
        'confirmed_by',
        'status',
        'approval_notes',
        'rejection_reason',
        'disbursement_notes',
        'confirmation_notes',
        'approved_at',
        'disbursed_at',
        'confirmed_at',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'disbursed_quantity' => 'decimal:2',
        'required_date' => 'date',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the bill of quantity
     */
    public function billOfQuantity(): BelongsTo
    {
        return $this->belongsTo(BillOfQuantity::class);
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
     * Get the user who requested
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who disbursed
     */
    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Get the user who confirmed (inspector)
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Approve the material request
     */
    public function approve(User $approver, float $quantity, ?string $notes = null): void
    {
        $this->approved_by = $approver->id;
        $this->approved_quantity = $quantity;
        $this->approval_notes = $notes;
        $this->approved_at = now();
        $this->status = 'approved';
        $this->save();
    }

    /**
     * Reject the material request
     */
    public function reject(User $approver, string $reason): void
    {
        $this->approved_by = $approver->id;
        $this->rejection_reason = $reason;
        $this->approved_at = now();
        $this->status = 'rejected';
        $this->save();
    }

    /**
     * Disburse materials
     */
    public function disburse(User $disburser, float $quantity, ?string $notes = null): void
    {
        $this->disbursed_by = $disburser->id;
        $this->disbursed_quantity = $quantity;
        $this->disbursement_notes = $notes;
        $this->disbursed_at = now();
        $this->status = 'disbursed';
        $this->save();
    }

    /**
     * Confirm material delivery (by inspector)
     */
    public function confirm(User $inspector, ?string $notes = null): void
    {
        $this->confirmed_by = $inspector->id;
        $this->confirmation_notes = $notes;
        $this->confirmed_at = now();
        $this->status = 'confirmed';
        $this->save();
    }

    /**
     * Cancel the request
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Check if request can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request can be disbursed
     */
    public function canBeDisbursed(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'disbursed';
    }

    /**
     * Check if requested quantity exceeds BOQ remaining amount
     */
    public function exceedsBoqLimit(): bool
    {
        if (!$this->bill_of_quantity_id) {
            return false;
        }

        return !$this->billOfQuantity->canRequestQuantity($this->requested_quantity);
    }

    /**
     * Get available quantity from BOQ
     */
    public function getAvailableQuantity(): float
    {
        if (!$this->bill_of_quantity_id) {
            return 0;
        }

        return $this->billOfQuantity->remaining_quantity;
    }

    /**
     * Get validation error for BOQ limit
     */
    public function getBoqLimitError(): string
    {
        if (!$this->bill_of_quantity_id) {
            return '';
        }

        $available = $this->getAvailableQuantity();
        return "Only {$available} {$this->billOfQuantity->unit} available. Requested: {$this->requested_quantity} {$this->billOfQuantity->unit}";
    }
}
