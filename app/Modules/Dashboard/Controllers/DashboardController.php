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
        return view('dashboard.index', [
            'user' => $request->user(),
            'recentLogins' => $this->dashboardService->recentLogins(),
        ]);
    }
}
