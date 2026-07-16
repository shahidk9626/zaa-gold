<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentTimelineService;
use Illuminate\Http\Request;

class PaymentLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = PaymentTransaction::with(['booking.customer', 'emiSchedule'])
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->gateway))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('transaction_number', 'like', $term)
                        ->orWhere('gateway_order_id', 'like', $term)
                        ->orWhere('gateway_payment_id', 'like', $term)
                        ->orWhereHas('booking', fn ($b) => $b->where('booking_number', 'like', $term)
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payment-logs.index', compact('logs'));
    }

    public function show(PaymentTransaction $paymentLog, PaymentTimelineService $paymentTimelineService)
    {
        $paymentLog->load(['booking.customer', 'emiSchedule', 'generatedBy']);
        $timeline = $paymentTimelineService->forTransaction($paymentLog);
        $receipt = \App\Models\BookingPayment::where('booking_id', $paymentLog->booking_id)
            ->when($paymentLog->emi_schedule_id, fn ($q) => $q->where('emi_schedule_id', $paymentLog->emi_schedule_id))
            ->where('status', 'Paid')
            ->latest('payment_date')
            ->first();
        $invoice = $receipt ? \App\Models\GstInvoice::where('payment_id', $receipt->id)->first() : null;
        $activityLogs = \App\Models\ActivityLog::where('description', 'like', '%' . $paymentLog->transaction_number . '%')
            ->orWhere('record_id', $paymentLog->booking_id)
            ->latest()
            ->get();

        return view('admin.payment-logs.show', compact('paymentLog', 'timeline', 'receipt', 'invoice', 'activityLogs'));
    }
}
