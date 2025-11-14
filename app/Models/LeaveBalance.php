<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type',
        'balance_days',
        'year',
        'accrual_rate',
        'used_days',
    ];

    protected $casts = [
        'balance_days' => 'decimal:2',
        'accrual_rate' => 'decimal:2',
        'used_days' => 'decimal:2',
    ];

    /**
     * Get the user that owns the leave balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave requests for this balance.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'user_id', 'user_id')
            ->where('leave_type', $this->leave_type)
            ->whereYear('start_date', $this->year);
    }

    /**
     * Get the available leave days.
     */
    public function getAvailableDaysAttribute(): float
    {
        return max(0, $this->balance_days - $this->used_days);
    }

    /**
     * Check if the user has sufficient balance for requested days.
     */
    public function hasSufficientBalance(float $requestedDays): bool
    {
        return $this->available_days >= $requestedDays;
    }

    /**
     * Add leave days to the balance.
     */
    public function addBalance(float $days): void
    {
        $this->increment('balance_days', $days);
    }

    /**
     * Use leave days from the balance.
     */
    public function useBalance(float $days): void
    {
        if ($this->hasSufficientBalance($days)) {
            $this->increment('used_days', $days);
        }
    }

    /**
     * Return leave days to the balance.
     */
    public function returnBalance(float $days): void
    {
        $this->decrement('used_days', max(0, min($days, $this->used_days)));
    }

    /**
     * Get or create a leave balance for a user.
     */
    public static function getOrCreate(int $userId, string $leaveType, int $year): self
    {
        return static::firstOrCreate([
            'user_id' => $userId,
            'leave_type' => $leaveType,
            'year' => $year,
        ], [
            'balance_days' => self::getDefaultBalance($leaveType),
            'accrual_rate' => self::getDefaultAccrualRate($leaveType),
            'used_days' => 0,
        ]);
    }

    /**
     * Get default balance days for leave type.
     */
    private static function getDefaultBalance(string $leaveType): float
    {
        $defaults = [
            'vacation' => 21.0,
            'sick' => 10.0,
            'personal' => 5.0,
            'bereavement' => 3.0,
            'maternity' => 90.0,
            'paternity' => 14.0,
        ];

        return $defaults[$leaveType] ?? 0;
    }

    /**
     * Get default accrual rate for leave type.
     */
    private static function getDefaultAccrualRate(string $leaveType): float
    {
        $rates = [
            'vacation' => 1.75,
            'sick' => 0.83,
            'personal' => 0.42,
            'bereavement' => 0,
            'maternity' => 0,
            'paternity' => 0,
        ];

        return $rates[$leaveType] ?? 0;
    }

    /**
     * Scope a query to only include balances for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to only include balances for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include balances for a specific leave type.
     */
    public function scopeForLeaveType($query, $leaveType)
    {
        return $query->where('leave_type', $leaveType);
    }

    /**
     * Get all leave types.
     */
    public static function getLeaveTypes(): array
    {
        return [
            'sick' => 'Sick Leave',
            'vacation' => 'Vacation',
            'personal' => 'Personal Leave',
            'bereavement' => 'Bereavement Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
        ];
    }

    /**
     * Get formatted leave type name.
     */
    public function getLeaveTypeNameAttribute(): string
    {
        return self::getLeaveTypes()[$this->leave_type] ?? ucfirst($this->leave_type);
    }
}
