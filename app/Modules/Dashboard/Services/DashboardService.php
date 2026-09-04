<?php

namespace App\Modules\Dashboard\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    public const SETTINGS_KEY = 'dashboard.enabled_cards';

    /**
     * Which card keys are currently enabled, admin-wide (see the Settings
     * module). Defaults to every card defined in config('dashboard.cards')
     * until an admin explicitly saves a subset.
     *
     * @return list<string>
     */
    public function enabledCardKeys(): array
    {
        return Setting::get(self::SETTINGS_KEY, array_keys(config('dashboard.cards')));
    }

    /**
     * Cards the given user may actually see right now: enabled by the
     * admin-wide setting *and* the user holds the card's required
     * permission (if any). Backend-authoritative — the settings screen is
     * a convenience, not the security boundary.
     *
     * @return list<string>
     */
    public function visibleCardKeysFor(User $user): array
    {
        $enabled = $this->enabledCardKeys();

        return collect(config('dashboard.cards'))
            ->filter(fn (array $card, string $key) => in_array($key, $enabled, true)
                && ($card['permission'] === null || $user->can($card['permission'])))
            ->keys()
            ->all();
    }

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
