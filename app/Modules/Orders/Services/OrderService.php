<?php

namespace App\Modules\Orders\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Support\AuditLogger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        return Order::query()->with('customer')->latest()->paginate(20);
    }

    /**
     * @param  array{customer_id: int, status: string, source?: string|null, delivery_address?: string|null, payment_method?: string|null, comment?: string|null, marketplace_order_id?: string|null, marketplace_order_name?: string|null, items: array<int, array{product_id: int, quantity: int}>}  $data
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($this->orderAttributes($data));

            $this->syncItems($order, $data['items']);

            $order->customer->recalculateOrderStats();

            $this->auditLogger->log('order.created', $order, [
                'status' => $order->status,
                'total_amount' => (string) $order->total_amount,
            ]);

            return $order->fresh('items');
        });
    }

    /**
     * @param  array{customer_id: int, status: string, source?: string|null, delivery_address?: string|null, payment_method?: string|null, comment?: string|null, marketplace_order_id?: string|null, marketplace_order_name?: string|null, items: array<int, array{product_id: int, quantity: int}>}  $data
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $previousCustomerId = $order->customer_id;

            $order->fill($this->orderAttributes($data));
            $order->save();

            $this->syncItems($order, $data['items']);

            $order->customer->recalculateOrderStats();

            if ($previousCustomerId !== $order->customer_id) {
                Customer::find($previousCustomerId)?->recalculateOrderStats();
            }

            $this->auditLogger->log('order.updated', $order, [
                'changes' => $order->getChanges(),
            ]);

            return $order->fresh('items');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function orderAttributes(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'status' => $data['status'],
            'source' => $data['source'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'comment' => $data['comment'] ?? null,
            'marketplace_order_id' => $data['marketplace_order_id'] ?? null,
            'marketplace_order_name' => $data['marketplace_order_name'] ?? null,
        ];
    }

    /**
     * Replaces the order's line items wholesale and recomputes the total.
     * Price is always snapshotted from the product's current price — never
     * taken from client input.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();

        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantity = (int) $item['quantity'];

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);

            $total += (float) $product->price * $quantity;
        }

        $order->update(['total_amount' => $total]);
    }
}
