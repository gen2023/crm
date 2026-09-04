<?php

namespace App\Modules\Settings\Services;

use App\Models\Setting;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\AuditLogger;

class SettingsService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DashboardService $dashboardService,
    ) {
    }

    /**
     * @return list<string>
     */
    public function enabledDashboardCards(): array
    {
        return $this->dashboardService->enabledCardKeys();
    }

    public function lowStockThreshold(): int
    {
        return $this->dashboardService->lowStockThreshold();
    }

    /**
     * @param  list<string>  $cards
     */
    public function updateDashboardSettings(array $cards, int $lowStockThreshold): void
    {
        Setting::set(DashboardService::SETTINGS_KEY, array_values($cards));
        Setting::set(DashboardService::LOW_STOCK_THRESHOLD_KEY, $lowStockThreshold);

        $this->auditLogger->log('settings.updated', Setting::firstWhere('key', DashboardService::SETTINGS_KEY), [
            'cards' => $cards,
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }
}
