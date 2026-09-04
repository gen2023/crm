<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request): View
    {
        $visible = $this->dashboardService->visibleCardKeysFor($request->user());

        return view('dashboard.index', [
            'visibleCards' => $visible,
            'recentLogins' => in_array('recent_logins', $visible, true) ? $this->dashboardService->recentLogins() : null,
            'recentOrders' => in_array('recent_orders', $visible, true) ? $this->dashboardService->recentOrders() : null,
            'lowStockProducts' => in_array('low_stock_products', $visible, true) ? $this->dashboardService->lowStockProducts() : null,
            'orderStatusCounts' => in_array('order_status_counts', $visible, true) ? $this->dashboardService->orderStatusCounts() : null,
        ]);
    }
}
