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
                    'available_qty' => $item->available_qty,
                    'reserved_qty' => $item->reserved_qty,
                    'sold_qty' => $item->sold_qty,
                    'current_qty' => $item->current_qty,
                    'min_stock' => $item->min_stock,
                    'max_stock' => $item->max_stock,
                    'status' => $item->status,
                    'remarks' => $item->remarks ?? 'N/A',
                ];
            });
            return response()->json(['data' => $inventory]);
        }

        $products = Product::where('status', 'active')->get();
        return view('admin.inventory.index', compact('products'));
    }

    public function store(StoreInventoryRequest $request)
    {
        $inventory = Inventory::create($request->validated());
        return response()->json(['success' => 'Inventory record created successfully', 'inventory' => $inventory]);
    }

    public function update(StoreInventoryRequest $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->update($request->validated());

        return response()->json(['success' => 'Inventory record updated successfully']);
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
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();

        return response()->json(['success' => 'Inventory record deleted successfully']);
    }

    public function adjust(Request $request, $id)
    {
        $request->validate([
            'transaction_type' => 'required|in:purchase,reserve,release,sale,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
        ]);

        $inventory = Inventory::findOrFail($id);
        $inventory->logTransaction(
            $request->transaction_type,
            $request->quantity,
            $request->remarks
        );

        return response()->json(['success' => 'Inventory stock adjusted successfully']);
    }

    public function transactions(Request $request)
    {
        if ($request->ajax()) {
            $txs = InventoryTransaction::with(['inventory.product', 'createdBy'])
                ->latest()
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->inventory->product->name ?? 'N/A',
                        'sku' => $item->inventory->product->sku ?? 'N/A',
                        'type' => ucfirst($item->transaction_type),
                        'quantity' => $item->quantity,
                        'old_qty' => $item->old_qty,
                        'new_qty' => $item->new_qty,
                        'remarks' => $item->remarks ?? 'N/A',
                        'created_by' => $item->createdBy->name ?? 'System',
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            return response()->json(['data' => $txs]);
        }
        return view('admin.inventory.transactions');
    }
}
