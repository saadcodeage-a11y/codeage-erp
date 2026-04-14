<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        return view('dashboard', [
            'dashboard' => $dashboardService->build(request()->user()),
        ]);
    }
}
