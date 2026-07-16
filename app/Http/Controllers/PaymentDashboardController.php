<?php

namespace App\Http\Controllers;

use App\Services\PaymentReportService;

class PaymentDashboardController extends Controller
{
    public function __construct(protected PaymentReportService $paymentReportService)
    {
    }

    public function index()
    {
        $stats = $this->paymentReportService->dashboardStats();
        $charts = $this->paymentReportService->chartData();

        return view('admin.payments.dashboard', compact('stats', 'charts'));
    }
}
