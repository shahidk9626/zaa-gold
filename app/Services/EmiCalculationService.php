<?php

namespace App\Services;

use App\Models\EmiPlan;
use Carbon\Carbon;

class EmiCalculationService
{
    /**
     * Calculate monthly EMI installment amount
     */
    public function calculateMonthlyInstallment(EmiPlan $plan, $principalAmount)
    {
        $principal = (float)$principalAmount;
        $rate = (float)$plan->interest_rate;
        $months = (int)$plan->duration_months;

        if ($months <= 0) {
            return 0.00;
        }

        if ($rate <= 0) {
            return round($principal / $months, 2);
        }

        if ($plan->interest_type === 'flat') {
            $totalInterest = $principal * ($rate / 100) * ($months / 12);
            $totalPayable = $principal + $totalInterest;
            return round($totalPayable / $months, 2);
        } else {
            // Reducing balance calculation
            $r = $rate / 12 / 100;
            $n = $months;
            $emi = ($principal * $r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
            return round($emi, 2);
        }
    }

    /**
     * Calculate monthly EMI installment amount (alias for calculation signatures)
     */
    public function calculateMonthlyEMI(EmiPlan $plan, $amount)
    {
        return $this->calculateMonthlyInstallment($plan, $amount);
    }

    /**
     * Calculate total payable amount (principal + interest)
     */
    public function calculateTotalPayable(EmiPlan $plan, $amount)
    {
        return round(((float)$amount) + $this->calculateInterest($plan, $amount), 2);
    }

    /**
     * Calculate processing fee based on booking amount
     */
    public function calculateProcessingFee(EmiPlan $plan, $amount)
    {
        $fee = (float)$plan->processing_fee;
        if ($plan->processing_fee_type === 'percent') {
            return round(((float)$amount) * ($fee / 100), 2);
        }
        return round($fee, 2);
    }

    /**
     * Calculate late fee based on unpaid EMI installment amount
     */
    public function calculateLateFee(EmiPlan $plan, $emiAmount)
    {
        $fee = (float)$plan->late_fee;
        if ($plan->late_fee_type === 'percent') {
            return round(((float)$emiAmount) * ($fee / 100), 2);
        }
        return round($fee, 2);
    }

    /**
     * Calculate outstanding balance
     */
    public function calculateOutstanding(EmiPlan $plan, $totalBooked, $paidAmount)
    {
        return round(((float)$totalBooked) - ((float)$paidAmount), 2);
    }

    /**
     * Calculate completion date of plan
     */
    public function calculateCompletionDate(EmiPlan $plan, $startDate)
    {
        $date = is_string($startDate) ? Carbon::parse($startDate) : $startDate;
        return $date->copy()->addMonths((int)$plan->duration_months);
    }

    /**
     * Calculate interest amount portion
     */
    public function calculateInterest(EmiPlan $plan, $amount)
    {
        $principal = (float)$amount;
        $rate = (float)$plan->interest_rate;
        $months = (int)$plan->duration_months;

        if ($months <= 0 || $rate <= 0) {
            return 0.00;
        }

        if ($plan->interest_type === 'flat') {
            return round($principal * ($rate / 100) * ($months / 12), 2);
        } else {
            $emi = $this->calculateMonthlyInstallment($plan, $principal);
            $totalPayable = $emi * $months;
            return round($totalPayable - $principal, 2);
        }
    }

    /**
     * Unified calculation output dictionary
     */
    public function calculate(EmiPlan $plan, $amount)
    {
        $installment = $this->calculateMonthlyInstallment($plan, $amount);
        $processingFee = $this->calculateProcessingFee($plan, $amount);
        $interest = $this->calculateInterest($plan, $amount);
        $totalPayable = $this->calculateTotalPayable($plan, $amount);
        $lateFee = $this->calculateLateFee($plan, $installment);
        $completionDate = $this->calculateCompletionDate($plan, now());

        return [
            'installment' => $installment,
            'processing_fee' => $processingFee,
            'interest' => $interest,
            'total_payable' => $totalPayable,
            'late_fee' => $lateFee,
            'completion_date' => $completionDate->format('Y-m-d'),
        ];
    }
}
