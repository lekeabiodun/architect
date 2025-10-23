<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit',
        'unit_cost',
        'currency',
        'category',
        'reorder_level',
        'specifications',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'reorder_level' => 'integer',
    ];

    /**
     * Get all inventories for this material
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get all material requests for this material
     */
    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    /**
     * Get total quantity across all inventories
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->inventories()->sum('quantity');
    }

    /**
     * Get available quantity (not allocated)
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->inventories()->sum('quantity') - $this->inventories()->sum('allocated_quantity');
    }

    /**
     * Check if material is below reorder level
     */
    public function isBelowReorderLevel(): bool
    {
        return $this->total_quantity <= $this->reorder_level;
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
