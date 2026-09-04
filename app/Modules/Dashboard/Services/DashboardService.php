<?php

namespace App\Modules\Dashboard\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * @return Collection<int, AuditLog>
     */
    public function recentLogins(int $limit = 5): Collection
    {
        return AuditLog::query()
            ->where('action', 'auth.login')
            ->with('actor')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 5): Collection
    {
        return Order::query()
            ->with('customer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function lowStockProducts(): Collection
    {
        return Product::query()
            ->where('stock', '<', config('dashboard.low_stock_threshold'))
            ->orderBy('stock')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function orderStatusCounts(): array
    {
        $counts = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(Order::STATUS_LABELS)
            ->keys()
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }
}
