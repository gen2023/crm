<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'ip',
        'utm',
        'total_orders_amount',
        'last_order_at',
        'orders_count',
        'completed_orders_count',
        'cancelled_orders_count',
    ];

    protected function casts(): array
    {
        return [
            'utm' => 'array',
            'total_orders_amount' => 'decimal:2',
            'last_order_at' => 'datetime',
        ];
    }

    /**
     * Percentage of this customer's finished orders that were completed
     * rather than cancelled. Null until they have at least one of either
     * (no orders yet — and therefore nothing to judge reliability from).
     */
    public function reliability(): ?float
    {
        $decided = $this->completed_orders_count + $this->cancelled_orders_count;

        if ($decided === 0) {
            return null;
        }

        return round($this->completed_orders_count / $decided * 100, 1);
    }
}
