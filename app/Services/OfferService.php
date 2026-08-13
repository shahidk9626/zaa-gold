<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\GoldBooking;
use Illuminate\Support\Facades\DB;

class OfferService
{
    /**
     * Create a new promo offer
     */
    public function createOffer(array $data): Offer
    {
        return DB::transaction(function () use ($data) {
            $plans = $data['plans'] ?? [];
            unset($data['plans']);

            $offer = Offer::create($data);

            if (!empty($plans)) {
                $offer->emiPlans()->sync($plans);
            }

            return $offer;
        });
    }

    /**
     * Update an existing promo offer
     */
    public function updateOffer($id, array $data): Offer
    {
        return DB::transaction(function () use ($id, $data) {
            $offer = Offer::findOrFail($id);

            $plans = $data['plans'] ?? [];
            unset($data['plans']);

            $offer->update($data);

            $offer->emiPlans()->sync($plans);

            return $offer;
        });
    }

    /**
     * Delete an offer (with rules checking)
     */
    public function deleteOffer($id): void
    {
        $offer = Offer::findOrFail($id);

        // Deletion Rule: Prevent deletion if used in >= 1 booking
        $isUsed = GoldBooking::where('offer_id', $offer->id)->exists();
        if ($isUsed) {
            throw new \Exception('Deletion blocked: This offer has already been applied to booking(s). You can deactivate or set it to Expired instead.');
        }

        $offer->delete();
    }

    /**
     * Toggle status manually
     */
    public function toggleStatus($id, string $status): Offer
    {
        $offer = Offer::findOrFail($id);
        $offer->status = $status;
        $offer->save();

        return $offer;
    }

    /**
     * Auto expire offers whose end_date has passed
     */
    public function autoExpireOffers(): int
    {
        $now = now();
        
        $expiredCount = Offer::where('status', 'Active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $now)
            ->update(['status' => 'Expired']);
            
        return $expiredCount;
    }

    /**
     * Generate query for Offer Analytics Report
     */
    public function getOffersReportQuery(array $filters = [])
    {
        $query = Offer::withTrashed()
            ->select('offers.*')
            ->selectSub(function ($q) {
                $q->from('gold_bookings')
                    ->whereColumn('gold_bookings.offer_id', 'offers.id')
                    ->whereNotIn('gold_bookings.status', ['Cancelled', 'Refund Initiated', 'Refunded'])
                    ->selectRaw('COUNT(*)');
            }, 'total_usage')
            ->selectSub(function ($q) {
                $q->from('gold_bookings')
                    ->whereColumn('gold_bookings.offer_id', 'offers.id')
                    ->whereNotIn('gold_bookings.status', ['Cancelled', 'Refund Initiated', 'Refunded'])
                    ->selectRaw('COALESCE(SUM(gold_bookings.savings_amount), 0)');
            }, 'total_savings')
            ->selectSub(function ($q) {
                $q->from('gold_bookings')
                    ->whereColumn('gold_bookings.offer_id', 'offers.id')
                    ->whereNotIn('gold_bookings.status', ['Cancelled', 'Refund Initiated', 'Refunded'])
                    ->selectRaw('COUNT(DISTINCT gold_bookings.customer_id)');
            }, 'active_customers')
            ->selectSub(function ($q) {
                $q->from('gold_bookings')
                    ->whereColumn('gold_bookings.offer_id', 'offers.id')
                    ->whereNotIn('gold_bookings.status', ['Cancelled', 'Refund Initiated', 'Refunded'])
                    ->selectRaw('COALESCE(SUM(gold_bookings.grand_total), 0)');
            }, 'revenue_impact');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('offers.created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
        }

        if (!empty($filters['status'])) {
            $query->where('offers.status', $filters['status']);
        }

        return $query;
    }
}
