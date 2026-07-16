<?php

namespace App\Http\Controllers\Customer;

use App\Models\BookingEmiSchedule;
use App\Models\GoldBooking;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmiController extends CustomerBaseController
{
    public function history(): View
    {
        $data = $this->customerService->getEmiHistory($this->customerId());

        return view('customer.emi.history', $data);
    }

    public function repay(): View
    {
        $upcomingEmis = $this->customerService->getUpcomingEmis($this->customerId());

        return view('customer.emi.repay', compact('upcomingEmis'));
    }

    public function payForm(int $scheduleId): View
    {
        $schedule = BookingEmiSchedule::with(['booking.product'])
            ->whereHas('booking', fn ($q) => $q->where('customer_id', $this->customerId()))
            ->findOrFail($scheduleId);

        return view('customer.emi.pay', compact('schedule'));
    }

    public function processPay(int $scheduleId, Request $request, PaymentService $paymentService): RedirectResponse
    {
        $schedule = BookingEmiSchedule::with('booking')
            ->whereHas('booking', fn ($q) => $q->where('customer_id', $this->customerId()))
            ->findOrFail($scheduleId);

        if ($schedule->status === 'Paid') {
            return redirect()->route('customer.emi.history')->with('error', 'This EMI is already paid.');
        }

        try {
            $transaction = $paymentService->initiateEmiGatewayPayment(
                $schedule,
                $request->user(),
                'customer_self',
                now()->addHours(24)
            );

            return redirect($transaction->payment_url)
                ->with('success', 'Payment transaction created. Redirecting to secure Cashfree checkout.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
