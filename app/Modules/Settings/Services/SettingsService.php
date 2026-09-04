<?php

namespace App\Modules\Settings\Services;

use App\Models\Setting;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\AuditLogger;

class SettingsService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * @return list<string>
     */
    public function enabledDashboardCards(): array
    {
        return Setting::get(DashboardService::SETTINGS_KEY, array_keys(config('dashboard.cards')));
    }

    /**
     * @param  list<string>  $cards
     */
    public function updateDashboardCards(array $cards): void
    {
        Setting::set(DashboardService::SETTINGS_KEY, array_values($cards));

        $this->auditLogger->log('settings.updated', Setting::firstWhere('key', DashboardService::SETTINGS_KEY), [
            'key' => DashboardService::SETTINGS_KEY,
            'value' => $cards,
        ]);
    }
}
