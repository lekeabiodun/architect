<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'duration_days',
        'edited_by',
        'edit_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'duration_days' => 'decimal:2',
    ];

    /**
     * Get the user that owns the leave request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved this leave request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who edited this leave request.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Check if the leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the leave request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the leave request.
     */
    public function approve(User $approver): void
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->rejection_reason = null;
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance();
    }

    /**
     * Reject the leave request.
     */
    public function reject(User $rejector, string $reason): void
    {
        $this->status = 'rejected';
        $this->approved_by = $rejector->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Calculate the duration of the leave request.
     */
    public function calculateDuration(): float
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        // Calculate business days (excluding weekends)
        $duration = 0;
        while ($start <= $end) {
            if (! $start->isWeekend()) {
                $duration++;
            }
            $start->addDay();
        }

        return (float) $duration;
    }

    /**
     * Update the leave balance when approved.
     */
    private function updateLeaveBalance(): void
    {
        $balance = LeaveBalance::firstOrCreate([
            'user_id' => $this->user_id,
            'leave_type' => $this->leave_type,
            'year' => $this->start_date->year,
        ], [
            'balance_days' => 0,
            'used_days' => 0,
        ]);

        $balance->increment('used_days', $this->duration_days);
    }

    /**
     * Return the deducted days to the balance (e.g. when an approved request is removed).
     */
    private function restoreLeaveBalance(): void
    {
        $balance = LeaveBalance::where([
            'user_id' => $this->user_id,
            'leave_type' => $this->leave_type,
            'year' => $this->start_date->year,
        ])->first();

        $balance?->returnBalance($this->duration_days);
    }

    /**
     * Check if the user has sufficient leave balance.
     */
    public function hasSufficientBalance(): bool
    {
        if (! $this->start_date) {
            return false;
        }

        $balance = LeaveBalance::where([
            'user_id' => $this->user_id,
            'leave_type' => $this->leave_type,
            'year' => $this->start_date->year,
        ])->first();

        if (! $balance) {
            return false;
        }

        return $balance->available_days >= $this->duration_days;
    }

    /**
     * Get the available leave balance for this request.
     */
    public function getAvailableBalanceAttribute(): float
    {
        if (! $this->start_date) {
            return 0;
        }

        $balance = LeaveBalance::where([
            'user_id' => $this->user_id,
            'leave_type' => $this->leave_type,
            'year' => $this->start_date->year,
        ])->first();

        return $balance ? $balance->available_days : 0;
    }

    /**
     * Scope a query to only include pending leave requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved leave requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected leave requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include requests for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Scope a query to only include requests for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Boot the model to calculate duration before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($leaveRequest) {
            $leaveRequest->duration_days = $leaveRequest->calculateDuration();
        });

        static::deleting(function ($leaveRequest) {
            // Return deducted days to the balance when an approved request is removed.
            if ($leaveRequest->isApproved()) {
                $leaveRequest->restoreLeaveBalance();
            }
        });
    }
}
