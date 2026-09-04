<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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

    /**
     * Recomputes every order-derived field from the orders table directly
     * (rather than incrementing/decrementing counters as orders change) —
     * simpler to reason about and immune to drift. Called by OrderService
     * whenever an order tied to this customer is created, updated, or
     * reassigned.
     */
    public function recalculateOrderStats(): void
    {
        $this->orders_count = $this->orders()->count();
        $this->completed_orders_count = $this->orders()->where('status', Order::STATUS_COMPLETED)->count();
        $this->cancelled_orders_count = $this->orders()->where('status', Order::STATUS_CANCELLED)->count();
        $this->total_orders_amount = $this->orders()->sum('total_amount');
        $this->last_order_at = $this->orders()->max('created_at');

        $this->save();
    }
}
