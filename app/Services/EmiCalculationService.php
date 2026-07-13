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
     * Registry of additional charges to calculate.
     * Future ready: to add another charge later, simply:
     * 1. Add a configuration field to the database/EMI plan.
     * 2. Add one calculation method here (e.g. calculateSecurityCharge).
     * 3. Register the charge in this array below.
     */
    protected $registeredCharges = [
        'finance_charge' => 'calculateFinanceCharge',
        'storage_charge' => 'calculateStorageCharge',
    ];

    /**
     * =========================================================
     * CALCULATION FLOW / SEQUENCE FOR FUTURE BOOKING MODULE
     * =========================================================
     * 
     * 1. Gold Value (weight * ratePerGram)
     *    ↓
     * 2. GST on Gold (gst_on_gold_percent)
     *    ↓
     * 3. Finance Charge (finance_charge_value based on type)
     *    ↓
     * 4. Storage / Insurance / Price Lock Charge (storage_charge_value based on type)
     *    ↓
     * 5. Total Charges (Sum of Finance Charge + Storage Charge + any future charges)
     *    ↓
     * 6. GST on Charges (gst_on_charges_percent)
     *    ↓
     * 7. Grand Total (Gold Value + GST on Gold + Total Charges + GST on Charges)
     *    ↓
     * 8. Monthly EMI (Grand Total / duration_months)
     */

    public function calculateGoldValue(EmiPlan $plan, $weight, $ratePerGram)
    {
        return round((float)$weight * (float)$ratePerGram, 2);
    }

    public function calculateGSTOnGold(EmiPlan $plan, $goldValue)
    {
        if (!$plan->gst_on_gold_enabled) {
            return 0.00;
        }
        $percent = (float)($plan->gst_on_gold_percent ?? 3.00);
        return round((float)$goldValue * ($percent / 100), 2);
    }

    public function calculateFinanceCharge(EmiPlan $plan, $goldValue)
    {
        if (!$plan->finance_charge_enabled) {
            return 0.00;
        }
        $type = strtolower($plan->finance_charge_type);
        $value = (float)$plan->finance_charge_value;
        if ($type === 'percentage' || $type === 'percent') {
            return round((float)$goldValue * ($value / 100), 2);
        }
        return round($value, 2);
    }

    public function calculateStorageCharge(EmiPlan $plan, $goldValue)
    {
        if (!$plan->storage_charge_enabled) {
            return 0.00;
        }
        $type = strtolower($plan->storage_charge_type);
        $value = (float)$plan->storage_charge_value;
        if ($type === 'percentage' || $type === 'percent') {
            return round((float)$goldValue * ($value / 100), 2);
        }
        return round($value, 2);
    }

    public function calculateTotalCharges(EmiPlan $plan, ...$charges)
    {
        if (!empty($charges)) {
            return round(array_sum(array_map('floatval', $charges)), 2);
        }
        return 0.00;
    }

    /**
     * Helper to compute total charges dynamically from registered charges
     */
    public function calculateTotalChargesDynamically(EmiPlan $plan, $goldValue)
    {
        $total = 0.00;
        foreach ($this->registeredCharges as $method) {
            if (method_exists($this, $method)) {
                $total += $this->$method($plan, $goldValue);
            }
        }
        return round($total, 2);
    }

    public function calculateGSTOnCharges(EmiPlan $plan, $totalCharges)
    {
        if (!$plan->gst_on_charges_enabled) {
            return 0.00;
        }
        $percent = (float)($plan->gst_on_charges_percent ?? 18.00);
        return round((float)$totalCharges * ($percent / 100), 2);
    }

    public function calculateGrandTotal(EmiPlan $plan, $goldValue, $gstOnGold, $totalCharges, $gstOnCharges)
    {
        $total = (float)$goldValue + (float)$gstOnGold + (float)$totalCharges + (float)$gstOnCharges;
        return $this->applyRounding($plan, $total);
    }

    public function calculateMonthlyEMI(EmiPlan $plan, $grandTotal)
    {
        $months = (int)$plan->duration_months;
        if ($months <= 0) {
            return 0.00;
        }
        $emi = (float)$grandTotal / $months;
        return $this->applyRounding($plan, $emi);
    }

    /**
     * Generate a price sheet summary dictionary (consumed by future Booking module)
     */
    public function calculatePriceSheet(EmiPlan $plan, $weight, $ratePerGram)
    {
        $goldValue = $this->calculateGoldValue($plan, $weight, $ratePerGram);
        $gstOnGold = $this->calculateGSTOnGold($plan, $goldValue);

        // Dynamically compute charges using the registered charges framework to remain future-ready
        $financeCharge = $this->calculateFinanceCharge($plan, $goldValue);
        $storageCharge = $this->calculateStorageCharge($plan, $goldValue);

        // Sum them up
        $totalCharges = $this->calculateTotalCharges($plan, $financeCharge, $storageCharge);
        $gstOnCharges = $this->calculateGSTOnCharges($plan, $totalCharges);
        $grandTotal = $this->calculateGrandTotal($plan, $goldValue, $gstOnGold, $totalCharges, $gstOnCharges);
        $monthlyEMI = $this->calculateMonthlyEMI($plan, $grandTotal);

        return [
            'gold_value' => $goldValue,
            'gst_on_gold' => $gstOnGold,
            'finance_charge' => $financeCharge,
            'storage_charge' => $storageCharge,
            'total_charges' => $totalCharges,
            'gst_on_charges' => $gstOnCharges,
            'grand_total' => $grandTotal,
            'monthly_emi' => $monthlyEMI,
        ];
    }

    /**
     * Apply rounding rules configured on the EMI Plan
     */
    protected function applyRounding(EmiPlan $plan, $amount)
    {
        $roundingType = strtolower(str_replace(' ', '_', $plan->rounding_type ?? 'none'));
        switch ($roundingType) {
            case 'nearest_rupee':
                return round($amount);
            case 'nearest_10':
                return round($amount / 10) * 10;
            case 'nearest_100':
                return round($amount / 100) * 100;
            case 'none':
            default:
                return round($amount, 2);
        }
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
    public function calculateInterest(EmiPlan $plan, $amount, $monthNo = null, $openingPrincipal = null, $monthlyEmi = null, $useFinancialEngine = false, $calc = null)
    {
        if ($monthNo === null) {
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

        if ($calc === null) {
            $calc = $this->calculate($plan, $amount);
        }
        return $this->calculateInterestBreakup($plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
    }

    /**
     * Unified calculation output dictionary
     */
    public function calculate(EmiPlan $plan, $amount)
    {
        $useFinancialEngine = (bool)($plan->gst_on_gold_enabled || $plan->finance_charge_enabled || $plan->storage_charge_enabled || $plan->gst_on_charges_enabled);

        if ($useFinancialEngine) {
            $goldValue = (float)$amount;
            $gstOnGold = $this->calculateGSTOnGold($plan, $goldValue);
            $financeCharge = $this->calculateFinanceCharge($plan, $goldValue);
            $storageCharge = $this->calculateStorageCharge($plan, $goldValue);
            $totalCharges = $this->calculateTotalCharges($plan, $financeCharge, $storageCharge);
            $gstOnCharges = $this->calculateGSTOnCharges($plan, $totalCharges);
            $grandTotal = $this->calculateGrandTotal($plan, $goldValue, $gstOnGold, $totalCharges, $gstOnCharges);
            $monthlyEMI = $this->calculateMonthlyEMI($plan, $grandTotal);

            $installment = $monthlyEMI;
            $totalPayable = $grandTotal;
            $interest = 0.00;
        } else {
            $installment = $this->calculateMonthlyInstallment($plan, $amount);
            $totalPayable = $this->calculateTotalPayable($plan, $amount);
            $interest = $this->calculateInterest($plan, $amount);
            $goldValue = (float)$amount;
            $gstOnGold = 0.00;
            $financeCharge = 0.00;
            $storageCharge = 0.00;
            $totalCharges = 0.00;
            $gstOnCharges = 0.00;
        }

        $processingFee = $this->calculateProcessingFee($plan, $amount);
        $lateFee = $this->calculateLateFee($plan, $installment);
        $completionDate = $this->calculateCompletionDate($plan, now());

        return [
            'installment' => $installment,
            'processing_fee' => $processingFee,
            'interest' => $interest,
            'total_payable' => $totalPayable,
            'late_fee' => $lateFee,
            'completion_date' => $completionDate->format('Y-m-d'),
            
            // Financial Configuration Engine details
            'use_financial_engine' => $useFinancialEngine,
            'gold_value' => $goldValue,
            'gst_on_gold' => $gstOnGold,
            'finance_charge' => $financeCharge,
            'storage_charge' => $storageCharge,
            'total_charges' => $totalCharges,
            'gst_on_charges' => $gstOnCharges,
            'grand_total' => $totalPayable,
            
            'gst_on_gold_enabled' => (bool)$plan->gst_on_gold_enabled,
            'finance_charge_enabled' => (bool)$plan->finance_charge_enabled,
            'storage_charge_enabled' => (bool)$plan->storage_charge_enabled,
            'gst_on_charges_enabled' => (bool)$plan->gst_on_charges_enabled,
        ];
    }

    /**
     * Generate outstanding payment/repayment schedule
     */
    public function generateOutstandingSchedule(EmiPlan $plan, $amount)
    {
        $duration = (int)$plan->duration_months;
        $calc = $this->calculate($plan, $amount);
        $monthlyEmi = $calc['installment'];
        $grandTotal = $calc['total_payable'];
        $useFinancialEngine = $calc['use_financial_engine'];
        
        $schedule = [];
        $openingPrincipal = $useFinancialEngine ? (float)$calc['gold_value'] : (float)$amount;
        $runningBalance = $grandTotal;
        
        for ($i = 1; $i <= $duration; $i++) {
            $interestAmount = $this->calculateInterestBreakup($plan, $amount, $i, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
            $principalAmount = $this->calculatePrincipalBreakup($plan, $amount, $i, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
            
            // Adjust for last month rounding
            if ($i === $duration) {
                $principalAmount = $openingPrincipal;
                $interestAmount = round($monthlyEmi - $principalAmount, 2);
                $closingPrincipal = 0.00;
                $runningBalance = 0.00;
            } else {
                $closingPrincipal = $this->calculateClosingBalance($openingPrincipal, $principalAmount);
                $runningBalance = round($runningBalance - $monthlyEmi, 2);
            }
            
            $dueDate = Carbon::now()->addMonths($i)->format('Y-m-d');
            
            $schedule[] = [
                'month_no' => $i,
                'due_date' => $dueDate,
                'opening_principal' => $openingPrincipal,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'monthly_emi' => $monthlyEmi,
                'closing_principal' => $closingPrincipal,
                'running_balance' => $runningBalance,
                'status' => 'Preview'
            ];
            
            $openingPrincipal = $closingPrincipal;
        }
        
        return $schedule;
    }

    /**
     * Calculate principal portion of the EMI
     */
    public function calculatePrincipalBreakup(EmiPlan $plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc)
    {
        $duration = (int)$plan->duration_months;
        if ($monthNo === $duration) {
            return $openingPrincipal;
        }
        if ($useFinancialEngine) {
            return round($calc['gold_value'] / $duration, 2);
        }
        if ($plan->interest_type === 'flat') {
            return round($amount / $duration, 2);
        }
        
        // Reducing balance: EMI - Interest Portion
        $interest = $this->calculateInterestBreakup($plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
        return round($monthlyEmi - $interest, 2);
    }

    /**
     * Calculate interest/charges portion of the EMI
     */
    public function calculateInterestBreakup(EmiPlan $plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc)
    {
        $duration = (int)$plan->duration_months;
        if ($useFinancialEngine) {
            $totalInterestOrCharges = $calc['grand_total'] - $calc['gold_value'];
            if ($monthNo === $duration) {
                return round($monthlyEmi - $openingPrincipal, 2);
            }
            return round($totalInterestOrCharges / $duration, 2);
        }
        if ($plan->interest_type === 'flat') {
            $rate = (float)$plan->interest_rate;
            $totalInterest = $amount * ($rate / 100) * ($duration / 12);
            if ($monthNo === $duration) {
                return round($monthlyEmi - $openingPrincipal, 2);
            }
            return round($totalInterest / $duration, 2);
        }
        
        // Reducing balance: Opening Principal * Monthly Rate
        $rate = (float)$plan->interest_rate;
        $monthlyRate = $rate / 12 / 100;
        if ($monthNo === $duration) {
            return round($monthlyEmi - $openingPrincipal, 2);
        }
        return round($openingPrincipal * $monthlyRate, 2);
    }

    /**
     * Calculate closing principal balance
     */
    public function calculateClosingBalance($openingPrincipal, $principalAmount)
    {
        return round($openingPrincipal - $principalAmount, 2);
    }

    /**
     * Generate actual EMI Schedule for a booking starting on booking date
     */
    public function generateSchedule(EmiPlan $plan, $amount, $bookingDate = null)
    {
        $duration = (int)$plan->duration_months;
        $calc = $this->calculate($plan, $amount);
        $monthlyEmi = $calc['installment'];
        $grandTotal = $calc['total_payable'];
        $useFinancialEngine = $calc['use_financial_engine'];
        
        $schedule = [];
        $openingPrincipal = $useFinancialEngine ? (float)$calc['gold_value'] : (float)$amount;
        $runningBalance = $grandTotal;
        
        $startDate = $bookingDate ? Carbon::parse($bookingDate) : Carbon::now();
        
        for ($i = 1; $i <= $duration; $i++) {
            $interestAmount = $this->calculateInterest($plan, $amount, $i, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
            $principalAmount = $this->calculatePrincipal($plan, $amount, $i, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
            
            // Adjust for last month rounding
            if ($i === $duration) {
                $principalAmount = $openingPrincipal;
                $interestAmount = round($monthlyEmi - $principalAmount, 2);
                $closingPrincipal = 0.00;
                $runningBalance = 0.00;
            } else {
                $closingPrincipal = $this->calculateClosingBalance($openingPrincipal, $principalAmount);
                $runningBalance = round($runningBalance - $monthlyEmi, 2);
            }
            
            // First EMI is due today (month_no = 1, monthsToAdd = 0)
            $dueDate = $this->calculateNextDueDate($startDate, $i - 1)->format('Y-m-d');
            
            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $dueDate,
                'opening_principal' => $openingPrincipal,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'emi_amount' => $monthlyEmi,
                'closing_principal' => $closingPrincipal,
                'outstanding_balance' => $runningBalance,
                'status' => 'Pending'
            ];
            
            $openingPrincipal = $closingPrincipal;
        }
        
        return $schedule;
    }

    /**
     * Calculate principal portion of the EMI (delegates to calculatePrincipalBreakup)
     */
    public function calculatePrincipal(EmiPlan $plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine = false, $calc = null)
    {
        if ($calc === null) {
            $calc = $this->calculate($plan, $amount);
        }
        return $this->calculatePrincipalBreakup($plan, $amount, $monthNo, $openingPrincipal, $monthlyEmi, $useFinancialEngine, $calc);
    }

    /**
     * Calculate next due date
     */
    public function calculateNextDueDate(Carbon $startDate, $monthsToAdd = 1)
    {
        return $startDate->copy()->addMonths((int)$monthsToAdd);
    }
}
