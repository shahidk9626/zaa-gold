<?php

namespace App\Http\Controllers\Customer;

use App\Models\GoldBooking;
use App\Services\CustomerService;
use App\Services\RefundCalculationService;
use App\Services\CancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CancellationRequestController extends CustomerBaseController
{
    protected $calculationService;
    protected $cancellationService;

    public function __construct(
        CustomerService $customerService,
        RefundCalculationService $calculationService,
        CancellationService $cancellationService
    ) {
        parent::__construct($customerService);
        $this->calculationService = $calculationService;
        $this->cancellationService = $cancellationService;
    }

    /**
     * Fetch real-time refund calculation preview for customer modal
     */
    public function preview($id): JsonResponse
    {
        $customerId = $this->customerId();
        $booking = GoldBooking::where('customer_id', $customerId)->findOrFail($id);

        try {
            $calculation = $this->calculationService->calculateRefund($booking);
            return response()->json(array_merge(['success' => true], $calculation));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Submit a cancellation request
     */
    public function submit(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
            'terms' => 'accepted',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
        ]);

        $customerId = $this->customerId();
        $booking = GoldBooking::where('customer_id', $customerId)->findOrFail($id);

        try {
            $this->cancellationService->createRequest(
                $booking,
                $request->cancellation_reason,
                $request->only(['bank_name', 'bank_account_number', 'bank_ifsc'])
            );

            return back()->with('success', 'Your cancellation request has been submitted successfully and is under review.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit cancellation request: ' . $e->getMessage())->withInput();
        }
    }
}
