<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Requests\UpdateDashboardSettingsRequest;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function edit(): View
    {
        return view('settings.edit', [
            'cards' => config('dashboard.cards'),
            'enabledCards' => $this->settingsService->enabledDashboardCards(),
            'lowStockThreshold' => $this->settingsService->lowStockThreshold(),
        ]);
    }

    public function update(UpdateDashboardSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->updateDashboardSettings(
            $request->validated('cards', []),
            $request->validated('low_stock_threshold'),
        );

        return redirect()->route('settings.edit')->with('status', 'Настройки сохранены.');
    }
}
