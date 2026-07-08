<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Http\Requests\StoreInventoryRequest;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $inventory = Inventory::with('product')->latest()->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'N/A',
                    'sku' => $item->product->sku ?? 'N/A',
                    'available_qty' => 0.00,
                    'reserved_qty' => 0.00,
                    'sold_qty' => 0.00,
                    'current_qty' => 0.00,
                    'min_stock' => 0.00,
                    'max_stock' => 0.00,
                    'status' => $item->status,
                    'remarks' => 'Dynamic Booking Workflow Active',
                ];
            });
            return response()->json(['data' => $inventory]);
        }

        $products = Product::where('status', 'active')->get();
        return view('admin.inventory.index', compact('products'));
    }

    public function store(StoreInventoryRequest $request)
    {
        return response()->json(['success' => 'Inventory records are managed dynamically based on customer EMI bookings.']);
    }

    public function update(StoreInventoryRequest $request, $id)
    {
        return response()->json(['success' => 'Inventory records are managed dynamically based on customer EMI bookings.']);
    }

    public function toggleStatus($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->status = $inventory->status === 'active' ? 'inactive' : 'active';
        $inventory->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        return response()->json(['success' => 'Inventory records cannot be manually deleted.']);
    }

    public function adjust(Request $request, $id)
    {
        return response()->json(['success' => 'Manual stock adjustments are disabled. Stock is locked automatically upon Customer Booking.']);
    }

    public function transactions(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(['data' => []]);
        }
        return view('admin.inventory.transactions');
    }
}
