<?php

namespace App\Services;

use App\Models\CancellationRequest;

class RefundService
{
    protected $cancellationService;

    public function __construct(CancellationService $cancellationService)
    {
        $this->cancellationService = $cancellationService;
    }

    /**
     * Transition status to 'Refund Initiated'
     */
    public function initiateRefund(CancellationRequest $request, string $remark): CancellationRequest
    {
        return $this->cancellationService->updateStatus($request, 'Refund Initiated', $remark);
    }

    /**
     * Transition status to 'Refund Completed' with transaction details
     */
    public function completeRefund(
        CancellationRequest $request,
        string $transactionNumber,
        string $refundMode,
        string $refundDate,
        string $remark
    ): CancellationRequest {
        return $this->cancellationService->updateStatus($request, 'Refund Completed', $remark, [
            'refund_transaction_number' => $transactionNumber,
            'refund_mode' => $refundMode,
            'refund_date' => $refundDate
        ]);
    }
}
