@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-white text-dark border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Add New Product</h4>
                        <p class="card-description text-muted">Register a new bullion product in the system catalog</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <form id="productForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name" class="text-dark">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" required class="form-control bg-white text-dark" placeholder="e.g. 24K Gold Coin 10g">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="sku" class="text-dark">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" id="sku" required class="form-control bg-white text-dark" placeholder="e.g. GOLD-COIN-10G">
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="weight" class="text-dark">Weight (Grams) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="weight" id="weight" required class="form-control bg-white text-dark" placeholder="e.g. 10.00">
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="purity" class="text-dark">Purity (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purity" id="purity" required class="form-control bg-white text-dark" placeholder="e.g. 99.99">
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="category" class="text-dark">Category <span class="text-danger">*</span></label>
                            <select name="category" id="category" required class="form-control bg-white text-dark">
                                <option value="">Select Category</option>
                                <option value="Coin">Coin</option>
                                <option value="Bar">Bar</option>
                                <option value="Jewelry">Jewelry</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="display_order" class="text-dark">Display Order</label>
                            <input type="number" name="display_order" id="display_order" class="form-control bg-white text-dark" placeholder="e.g. 0">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="status" class="text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" required class="form-control bg-white text-dark">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 form-group">
                            <label for="description" class="text-dark">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-control bg-white text-dark" placeholder="Product details description..."></textarea>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="thumbnail" class="text-dark">Thumbnail Image</label>
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control bg-white text-dark" style="height: auto;">
                            <small class="text-muted">Allowed types: jpeg, png, jpg. Max size: 2MB.</small>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="gallery_images" class="text-dark">Gallery Images</label>
                            <input type="file" name="gallery_images[]" id="gallery_images" multiple class="form-control bg-white text-dark" style="height: auto;">
                            <small class="text-muted">Upload multiple images for gallery display.</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <i class="mdi mdi-check mr-1"></i> Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#productForm').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let submitBtn = $('#submitBtn');

            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Product Saved',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('products.index') }}";
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Save Product');
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                        });
                    } else {
                        errorMsg = xhr.responseJSON.error || 'Something went wrong';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: errorMsg,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>
@endpush
