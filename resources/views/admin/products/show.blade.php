@extends('layouts.app')

@section('content')
<style>
    .product-cover {
        height: 200px;
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%);
        border-radius: 1rem;
        position: relative;
    }
    .product-header-card {
        margin-top: -60px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 1rem;
    }
    .product-thumbnail-container {
        width: 120px;
        height: 120px;
        border-radius: 1rem;
        overflow: hidden;
        border: 4px solid #ffffff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="product-cover mb-4"></div>

        <div class="card product-header-card text-dark p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="product-thumbnail-container bg-light d-flex align-items-center justify-content-center">
                        @if($product->thumbnail)
                            <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-100 h-100 object-cover" alt="thumbnail" />
                        @else
                            <i class="mdi mdi-image text-muted" style="font-size: 3rem;"></i>
                        @endif
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1 text-dark font-weight-bold">{{ $product->name }}</h4>
                    <p class="mb-0 text-muted">
                        Category: <strong>{{ $product->category }}</strong> | SKU: <strong>{{ $product->sku }}</strong>
                    </p>
                </div>
                <div class="col-md-auto text-right mt-3 mt-md-0">
                    <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="card bg-white text-dark border shadow-sm p-4">
            <h5 class="text-primary font-weight-bold mb-4">Bullion Specification Details</h5>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <label class="small text-muted d-block uppercase">Weight</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ number_format($product->weight, 2) }} Grams</span>
                </div>
                <div class="col-md-4 mb-4">
                    <label class="small text-muted d-block uppercase">Purity</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ number_format($product->purity, 2) }}% Pure Gold</span>
                </div>
                <div class="col-md-4 mb-4">
                    <label class="small text-muted d-block uppercase">Status</label>
                    <span class="badge {{ $product->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($product->status) }}</span>
                </div>
                <div class="col-12 mb-4">
                    <label class="small text-muted d-block uppercase">Description</label>
                    <p class="text-dark">{{ $product->description ?: 'No description provided.' }}</p>
                </div>
            </div>

            @if($product->gallery_images && count($product->gallery_images) > 0)
                <h5 class="text-primary font-weight-bold mb-3 mt-2">Gallery Display</h5>
                <div class="row">
                    @foreach($product->gallery_images as $img)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="border rounded p-1">
                                <img src="{{ asset('storage/' . $img) }}" class="w-100 rounded" style="height: 150px; object-fit: cover;">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
