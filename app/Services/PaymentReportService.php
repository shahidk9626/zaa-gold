<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\GoldBooking;
use App\Models\PaymentTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentReportService
{
    public function dashboardStats(): array
    {
        $successful = PaymentTransaction::where('payment_status', 'Success');
        $today = now()->toDateString();

        $total = PaymentTransaction::count();
        $successCount = PaymentTransaction::where('payment_status', 'Success')->count();

        return [
            'today_collection' => (float) (clone $successful)->whereDate('paid_at', $today)->sum('amount'),
            'monthly_collection' => (float) (clone $successful)->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount'),
            'yearly_collection' => (float) (clone $successful)->whereYear('paid_at', now()->year)->sum('amount'),
            'successful_payments' => $successCount,
            'pending_payments' => PaymentTransaction::whereIn('payment_status', ['Pending', 'Processing'])->count(),
            'failed_payments' => PaymentTransaction::where('payment_status', 'Failed')->count(),
            'expired_links' => PaymentTransaction::where('link_status', 'Expired')->count(),
            'total_links' => PaymentTransaction::whereNotNull('payment_url')->count(),
            'gateway_collection' => (float) PaymentTransaction::where('payment_status', 'Success')->sum('amount'),
            'pending_collection' => (float) PaymentTransaction::whereIn('payment_status', ['Pending', 'Processing'])->sum('amount'),
            'outstanding_amount' => $this->outstandingAmount(),
            'success_rate' => $total > 0 ? round(($successCount / $total) * 100, 2) : 0,
        ];
    }

    public function chartData(): array
    {
        return [
            'daily_collection' => $this->collectionByDate(now()->subDays(13), now(), 'day'),
            'monthly_collection' => $this->collectionByDate(now()->subMonths(11)->startOfMonth(), now(), 'month'),
            'success_failed' => PaymentTransaction::selectRaw('payment_status, COUNT(*) as total')
                ->groupBy('payment_status')
                ->pluck('total', 'payment_status')
                ->toArray(),
            'payment_mode_trend' => BookingPayment::selectRaw('payment_mode, SUM(amount_paid) as total')
                ->groupBy('payment_mode')
                ->pluck('total', 'payment_mode')
                ->toArray(),
        ];
    }

    public function customerSummary(User $customer): array
    {
        $transactions = PaymentTransaction::where('customer_id', $customer->id);
        $paid = (clone $transactions)->where('payment_status', 'Success')->sum('amount');
        $pending = (clone $transactions)->whereIn('payment_status', ['Pending', 'Processing'])->sum('amount');
        $failed = (clone $transactions)->where('payment_status', 'Failed')->count();
        $lastPayment = (clone $transactions)->latest('paid_at')->first();

        return [
            'total_paid' => (float) $paid,
            'pending' => (float) $pending,
            'outstanding' => $this->outstandingAmount($customer->id),
            'failed_payments' => $failed,
            'last_payment' => $lastPayment?->paid_at,
            'last_gateway' => $lastPayment?->gateway,
        ];
    }

    public function bookingSummary(GoldBooking $booking): array
    {
        $transactions = PaymentTransaction::where('booking_id', $booking->id);

        return [
            'booking_payment' => (clone $transactions)->where('payment_type', 'booking')->latest()->first(),
            'emi_payments' => (clone $transactions)->where('payment_type', 'emi')->get(),
            'pending_emi' => \App\Models\BookingEmiSchedule::where('booking_id', $booking->id)->whereIn('status', ['Pending', 'Overdue'])->count(),
            'gateway_transactions' => (clone $transactions)->latest()->get(),
            'payment_links' => (clone $transactions)->whereNotNull('payment_url')->latest()->get(),
        ];
    }

    public function reportQuery(array $filters = [])
    {
        return PaymentTransaction::with(['booking.customer', 'emiSchedule', 'generatedBy'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $term = '%' . $search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('transaction_number', 'like', $term)
                        ->orWhere('gateway_order_id', 'like', $term)
                        ->orWhere('gateway_payment_id', 'like', $term)
                        ->orWhereHas('booking', fn ($b) => $b->where('booking_number', 'like', $term)
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['payment_type'] ?? null, fn ($q, $type) => $q->where('payment_type', $type))
            ->when($filters['gateway'] ?? null, fn ($q, $gateway) => $q->where('gateway', $gateway))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['booking_id'] ?? null, fn ($q, $id) => $q->where('booking_id', $id))
            ->when($filters['staff_id'] ?? null, fn ($q, $id) => $q->where('generated_by_id', $id))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }

    protected function collectionByDate(Carbon $from, Carbon $to, string $unit): array
    {
        $format = $unit === 'month' ? 'Y-m' : 'Y-m-d';

        return PaymentTransaction::query()
            ->where('payment_status', 'Success')
            ->whereBetween('paid_at', [$from, $to])
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (PaymentTransaction $transaction) => $transaction->paid_at?->format($format) ?? 'Unknown')
            ->map(fn (Collection $transactions) => (float) $transactions->sum('amount'))
            ->sortKeys()
            ->toArray();
    }

    protected function outstandingAmount(?int $customerId = null): float
    {
        $bookings = GoldBooking::query()
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->whereNotIn('status', ['Cancelled', 'Refunded'])
            ->get();

        return (float) $bookings->sum(function (GoldBooking $booking) {
            $paid = BookingPayment::where('booking_id', $booking->id)->where('status', 'Paid')->sum('amount_paid');
            return max((float) $booking->grand_total - (float) $paid, 0);
        });
    }
}
