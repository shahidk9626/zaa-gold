<?php

namespace App\Http\Controllers;

use App\Models\GoldPrice;
use App\Models\GoldPriceHistory;
use App\Http\Requests\StoreGoldPriceRequest;
use Illuminate\Http\Request;

class GoldPriceController extends Controller
{
    public function index(Request $request)
    {
        $latestPrice = GoldPrice::where('status', 'active')->latest()->first();
        $prices = GoldPrice::latest()->get();
        return view('admin.gold-prices.index', compact('latestPrice', 'prices'));
    }

    public function store(StoreGoldPriceRequest $request)
    {
        $price = GoldPrice::create($request->validated());
        return response()->json(['success' => 'Gold price created successfully', 'price' => $price]);
    }

    public function update(StoreGoldPriceRequest $request, $id)
    {
        $price = GoldPrice::findOrFail($id);
        $price->update($request->validated());

        return response()->json(['success' => 'Gold price updated successfully and history recorded.']);
    }

    public function toggleStatus($id)
    {
        $price = GoldPrice::findOrFail($id);
        $price->status = $price->status === 'active' ? 'inactive' : 'active';
        $price->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        $price = GoldPrice::findOrFail($id);
        $price->delete();

        return response()->json(['success' => 'Gold price record deleted successfully']);
    }

    public function history(Request $request)
    {
        if ($request->ajax()) {
            $histories = GoldPriceHistory::with(['goldPrice', 'updatedBy'])
                ->latest()
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'gold_type' => $item->gold_type,
                        'old_price' => $item->old_price,
                        'new_price' => $item->new_price,
                        'remarks' => $item->goldPrice->remarks ?? 'N/A',
                        'updated_by' => $item->updatedBy->name ?? 'System',
                        'updated_at' => $item->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            return response()->json(['data' => $histories]);
        }
        return view('admin.gold-prices.history');
    }
}
