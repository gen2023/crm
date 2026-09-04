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
        $user = $request->user();

        return view('dashboard.index', [
            'recentLogins' => $this->dashboardService->recentLogins(),
            'recentOrders' => $user->can('orders.view') ? $this->dashboardService->recentOrders() : null,
            'lowStockProducts' => $user->can('products.view') ? $this->dashboardService->lowStockProducts() : null,
            'orderStatusCounts' => $user->can('orders.view') ? $this->dashboardService->orderStatusCounts() : null,
        ]);
    }
}
