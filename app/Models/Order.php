<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Order statuses are not free text — only "completed"/"cancelled" ever
     * feed Customer::recalculateOrderStats()'s reliability inputs, per the
     * project owner.
     */
    public const STATUS_NEW = 'new';

    public const STATUS_SHIPPING = 'shipping';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_SHIPPING => 'Доставляется',
        self::STATUS_COMPLETED => 'Завершён',
        self::STATUS_CANCELLED => 'Отменён',
    ];

    protected $fillable = [
        'customer_id',
        'status',
        'source',
        'delivery_address',
        'payment_method',
        'comment',
        'marketplace_order_id',
        'marketplace_order_name',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
