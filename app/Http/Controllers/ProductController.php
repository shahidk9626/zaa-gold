<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::latest()->get();
            return response()->json(['data' => $products]);
        }
        return view('admin.products.index');
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        if ($request->hasFile('gallery_images')) {
            $gallery = [];
            foreach ($request->file('gallery_images') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery_images'] = $gallery;
        }

        $product = Product::create($data);

        // Auto-create associated Inventory record with 0 qty
        if (class_exists(\App\Models\Inventory::class)) {
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'available_qty' => 0,
                'reserved_qty' => 0,
                'sold_qty' => 0,
                'min_stock' => 5,
                'max_stock' => 1000,
                'status' => 'active'
            ]);
        }

        return response()->json(['success' => 'Product created successfully', 'product' => $product]);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        if ($request->hasFile('gallery_images')) {
            // Delete old gallery if necessary, or merge them. Let's replace.
            if ($product->gallery_images) {
                foreach ($product->gallery_images as $oldImg) {
                    Storage::disk('public')->delete($oldImg);
                }
            }
            $gallery = [];
            foreach ($request->file('gallery_images') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery_images'] = $gallery;
        }

        $product->update($data);

        return response()->json(['success' => 'Product updated successfully']);
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete associated files
        if ($product->thumbnail) {
            Storage::disk('public')->delete($product->thumbnail);
        }
        if ($product->gallery_images) {
            foreach ($product->gallery_images as $img) {
                Storage::disk('public')->delete($img);
            }
        }

        $product->delete();

        return response()->json(['success' => 'Product deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $product = Product::find($id);
            if ($product) {
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                if ($product->gallery_images) {
                    foreach ($product->gallery_images as $img) {
                        Storage::disk('public')->delete($img);
                    }
                }
                $product->delete();
                $count++;
            }
        }

        return response()->json(['success' => "{$count} products deleted successfully."]);
    }
}
