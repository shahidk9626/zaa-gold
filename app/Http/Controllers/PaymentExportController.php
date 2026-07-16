<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentExportController extends Controller
{
    public function __construct(protected PaymentReportService $paymentReportService)
    {
    }

    public function export(Request $request, string $module, string $type)
    {
        $query = $this->paymentReportService->reportQuery($request->all());

        match ($module) {
            'links' => $query->whereNotNull('payment_url'),
            'failed' => $query->where('payment_status', 'Failed'),
            'reconciliation', 'logs' => null,
            default => abort(404),
        };

        $transactions = $query->latest()->get();
        $fileName = 'Payment_' . ucfirst($module) . '_' . now()->format('YmdHis');

        if ($type === 'pdf') {
            return Pdf::loadView('admin.payments.export-pdf', compact('transactions', 'module'))
                ->download($fileName . '.pdf');
        }

        $extension = $type === 'excel' ? 'xls' : 'csv';
        $contentType = $type === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';

        return new StreamedResponse(function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Transaction', 'Gateway', 'Order ID', 'Payment ID', 'Booking', 'Customer', 'Type', 'EMI', 'Amount', 'Status', 'Failure', 'Webhook', 'Created']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_number,
                    $transaction->gateway,
                    $transaction->gateway_order_id,
                    $transaction->gateway_payment_id,
                    $transaction->booking->booking_number ?? 'N/A',
                    $transaction->booking->customer->name ?? $transaction->customer->name ?? 'N/A',
                    $transaction->payment_type,
                    $transaction->emiSchedule?->installment_number,
                    $transaction->amount,
                    $transaction->payment_status,
                    $transaction->failure_reason,
                    $transaction->webhook_processed_at,
                    $transaction->created_at,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.' . $extension . '"',
        ]);
    }
}
