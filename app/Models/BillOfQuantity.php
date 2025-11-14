<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfQuantity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'requestable_quantity',
        'consumed_quantity',
        'unit_rate',
        'total_amount',
        'category',
        'notes',
        'order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requestable_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->requestable_quantity - $this->consumed_quantity);
    }

    public function canRequestQuantity(float $requestedAmount): bool
    {
        return $this->remaining_quantity >= $requestedAmount;
    }

    public function consumeQuantity(float $amount): void
    {
        $this->consumed_quantity += $amount;
        $this->save();
    }

    public function returnQuantity(float $amount): void
    {
        $this->consumed_quantity = max(0, $this->consumed_quantity - $amount);
        $this->save();
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->requestable_quantity == 0) {
            return 0;
        }
        
        return ($this->consumed_quantity / $this->requestable_quantity) * 100;
    }

    protected static function booted()
    {
        static::saving(function ($billOfQuantity) {
            if ($billOfQuantity->quantity && $billOfQuantity->unit_rate) {
                $billOfQuantity->total_amount = $billOfQuantity->quantity * $billOfQuantity->unit_rate;
            }
        });
    }
}
