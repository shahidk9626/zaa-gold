<?php

namespace App\Http\Controllers;

use App\Models\BookingEmiSchedule;
use App\Models\PaymentTransaction;
use App\Services\PaymentLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function index(Request $request)
    {
        $links = PaymentTransaction::with(['booking.customer', 'emiSchedule', 'generatedBy'])
            ->whereIn('payment_type', ['emi', 'booking'])
            ->whereNotNull('payment_url')
            ->when($request->filled('status'), fn ($q) => $q->where('link_status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('transaction_number', 'like', $term)
                        ->orWhereHas('booking', fn ($b) => $b->where('booking_number', 'like', $term)
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payment-links.index', compact('links'));
    }

    public function show(PaymentTransaction $paymentLink)
    {
        $paymentLink->load(['booking.customer', 'emiSchedule', 'generatedBy']);
        $timeline = \App\Models\ActivityLog::whereIn('module_name', ['payment_link', 'gold_booking'])
            ->where(function ($q) use ($paymentLink) {
                $q->where('description', 'like', '%' . $paymentLink->transaction_number . '%')
                    ->orWhere('record_id', $paymentLink->booking_id);
            })
            ->latest()
            ->get();

        return view('admin.payment-links.show', compact('paymentLink', 'timeline'));
    }

    public function generate(int $bookingId, int $scheduleId, PaymentLinkService $paymentLinkService): RedirectResponse
    {
        $schedule = BookingEmiSchedule::with('booking')
            ->where('booking_id', $bookingId)
            ->findOrFail($scheduleId);

        if ($schedule->status === 'Paid') {
            return back()->with('error', 'This EMI installment is already paid.');
        }

        try {
            $link = $paymentLinkService->generateLink($schedule, auth()->user());

            return back()->with('success', 'Payment link generated: ' . $link->payment_url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to generate payment link: ' . $e->getMessage());
        }
    }

    public function regenerate(PaymentTransaction $paymentLink, PaymentLinkService $paymentLinkService): RedirectResponse
    {
        try {
            $newLink = $paymentLinkService->regenerateLink($paymentLink, auth()->user());

            return redirect()->route('payment-links.show', $newLink)->with('success', 'Payment link regenerated.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to regenerate link: ' . $e->getMessage());
        }
    }

    public function copy(PaymentTransaction $paymentLink, PaymentLinkService $paymentLinkService): RedirectResponse
    {
        $paymentLinkService->markShared($paymentLink);

        return back()->with('success', 'Payment link ready to copy: ' . $paymentLink->payment_url);
    }
}
