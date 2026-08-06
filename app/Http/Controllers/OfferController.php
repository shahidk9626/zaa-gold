<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\EmiPlan;
use App\Services\OfferService;
use App\Services\OfferEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    protected OfferService $offerService;
    protected OfferEligibilityService $eligibilityService;

    public function __construct(OfferService $offerService, OfferEligibilityService $eligibilityService)
    {
        $this->offerService = $offerService;
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Display a listing of offers
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $offers = Offer::with('emiPlans')->latest()->get();
            return response()->json(['data' => $offers]);
        }
        return view('admin.offers.index');
    }

    /**
     * Show creation form
     */
    public function create()
    {
        $plans = EmiPlan::where('status', 'active')->orderBy('plan_name')->get();
        return view('admin.offers.create', compact('plans'));
    }

    /**
     * Store new offer
     */
    public function store(Request $request)
    {
        $rules = [
            'offer_code' => 'required|string|max:100|unique:offers,offer_code',
            'offer_name' => 'required|string|max:255',
            'offer_type' => 'required|string|in:percentage,fixed,emi',
            'percentage' => 'required_if:offer_type,percentage|nullable|numeric|min:0.01|max:100.00',
            'fixed_amount' => 'required_if:offer_type,fixed|nullable|numeric|min:0.01',
            'required_emi_count' => 'required_if:offer_type,emi|nullable|integer|min:1',
            'free_emi_count' => 'required_if:offer_type,emi|nullable|integer|min:1',
            'offer_description' => 'nullable|string',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'nullable|integer|min:0',
            'status' => 'required|string|in:Draft,Active,Inactive,Expired',
            'plans' => 'nullable|array',
            'plans.*' => 'exists:emi_plans,id',
        ];

        $data = $request->validate($rules);

        // Upload banner if present
        if ($request->hasFile('banner_image')) {
            $file = $request->file('banner_image');
            $filename = 'offer_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('offers', $filename, 'public');
            $data['banner'] = $path;
        }

        $offer = $this->offerService->createOffer($data);

        // Capture manual activity logging
        app(\App\Services\AuditTrailService::class)->captureEvent(
            'offer',
            $offer->id,
            'create',
            null,
            $offer->toArray(),
            "Promotional offer [{$offer->offer_code}] was created successfully."
        );

        return response()->json(['success' => 'Promotional offer created successfully', 'offer' => $offer]);
    }

    /**
     * Display a specific offer
     */
    public function show($id)
    {
        $offer = Offer::with('emiPlans')->findOrFail($id);
        return response()->json($offer);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $offer = Offer::with('emiPlans')->findOrFail($id);
        $plans = EmiPlan::where('status', 'active')->orderBy('plan_name')->get();
        return view('admin.offers.edit', compact('offer', 'plans'));
    }

    /**
     * Update an offer
     */
    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);

        $rules = [
            'offer_code' => 'required|string|max:100|unique:offers,offer_code,' . $id,
            'offer_name' => 'required|string|max:255',
            'offer_type' => 'required|string|in:percentage,fixed,emi',
            'percentage' => 'required_if:offer_type,percentage|nullable|numeric|min:0.01|max:100.00',
            'fixed_amount' => 'required_if:offer_type,fixed|nullable|numeric|min:0.01',
            'required_emi_count' => 'required_if:offer_type,emi|nullable|integer|min:1',
            'free_emi_count' => 'required_if:offer_type,emi|nullable|integer|min:1',
            'offer_description' => 'nullable|string',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'nullable|integer|min:0',
            'status' => 'required|string|in:Draft,Active,Inactive,Expired',
            'plans' => 'nullable|array',
            'plans.*' => 'exists:emi_plans,id',
        ];

        $data = $request->validate($rules);
        $oldOfferData = $offer->toArray();

        // Upload banner if present
        if ($request->hasFile('banner_image')) {
            // Delete old banner if exists
            if ($offer->banner) {
                Storage::disk('public')->delete($offer->banner);
            }
            $file = $request->file('banner_image');
            $filename = 'offer_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('offers', $filename, 'public');
            $data['banner'] = $path;
        }

        $this->offerService->updateOffer($id, $data);
        $offer->refresh();

        // Capture manual activity logging
        app(\App\Services\AuditTrailService::class)->captureEvent(
            'offer',
            $offer->id,
            'update',
            $oldOfferData,
            $offer->toArray(),
            "Promotional offer [{$offer->offer_code}] was updated successfully."
        );

        return response()->json(['success' => 'Promotional offer updated successfully']);
    }

    /**
     * Delete an offer
     */
    public function destroy($id)
    {
        try {
            $offer = Offer::findOrFail($id);
            $oldOfferData = $offer->toArray();

            $this->offerService->deleteOffer($id);

            // Capture manual activity logging
            app(\App\Services\AuditTrailService::class)->captureEvent(
                'offer',
                $id,
                'delete',
                $oldOfferData,
                null,
                "Promotional offer [{$oldOfferData['offer_code']}] was soft-deleted."
            );

            return response()->json(['success' => 'Offer deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Toggle status manually
     */
    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Draft,Active,Inactive,Expired',
        ]);

        $offer = Offer::findOrFail($id);
        $oldStatus = $offer->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return response()->json(['success' => 'Status is already ' . $newStatus]);
        }

        $this->offerService->toggleStatus($id, $newStatus);

        // Capture manual activity logging
        app(\App\Services\AuditTrailService::class)->captureEvent(
            'offer',
            $offer->id,
            'status_change',
            ['status' => $oldStatus],
            ['status' => $newStatus],
            "Status of Promotional offer [{$offer->offer_code}] was changed from {$oldStatus} to {$newStatus}."
        );

        return response()->json(['success' => 'Offer status updated to ' . $newStatus]);
    }
}
