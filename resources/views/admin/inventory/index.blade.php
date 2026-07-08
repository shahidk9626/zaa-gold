@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Bullion Stock Inventory</h4>
                        <p class="card-description text-muted">Monitor and adjust product inventory levels and safety stocks</p>
                    </div>
                    <div>
                        @if(hasPermission('inventory.view'))
                            <a href="{{ route('inventory.transactions') }}" class="btn btn-outline-primary btn-sm mr-2">
                                <i class="mdi mdi-history mr-1"></i> Stock Ledger Logs
                            </a>
                        @endif
                        @if(hasPermission('inventory.adjust'))
                            <button class="btn btn-primary btn-sm" onclick="openAdjustmentModal()">
                                <i class="mdi mdi-plus-minus-box mr-1"></i> Adjust Stock
                            </button>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="inventoryTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th class="text-right">Available Qty</th>
                                <th class="text-right">Reserved Qty</th>
                                <th class="text-right">Sold Qty</th>
                                <th class="text-right">Current Stock</th>
                                <th class="text-right">Safety Min</th>
                                <th class="text-right">Safety Max</th>
                                <th class="text-center">Stock Level</th>
                                <th class="text-center">Status</th>
                                @if(hasPermission('inventory.adjust'))
                                    <th class="text-center">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
@if(hasPermission('inventory.adjust'))
<div class="modal fade" id="adjustModal" tabindex="-1" role="dialog" aria-labelledby="adjustModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-white text-dark border">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-dark" id="adjustModalTitle">Stock Adjustment</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="adjustForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="inventoryId" name="id">
                    
                    <div class="form-group mb-3">
                        <label for="productSelect" class="text-dark">Select Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="productSelect" required class="form-control bg-white text-dark">
                            <option value="">Choose a product</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" data-inventory-id="{{ $prod->inventory->id ?? '' }}">{{ $prod->name }} (SKU: {{ $prod->sku }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="txType" class="text-dark">Transaction Type <span class="text-danger">*</span></label>
                        <select name="transaction_type" id="txType" required class="form-control bg-white text-dark">
                            <option value="purchase">Purchase (Add to Available)</option>
                            <option value="reserve">Reserve (Move Available -> Reserved)</option>
                            <option value="release">Release (Move Reserved -> Available)</option>
                            <option value="sale">Sale (Move Reserved -> Sold)</option>
                            <option value="adjustment">Direct Adjustment (Override Available Qty)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="quantity" class="text-dark">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="quantity" id="quantity" required class="form-control bg-white text-dark" placeholder="e.g. 10.00">
                    </div>

                    <div class="form-group mb-4">
                        <label for="remarks" class="text-dark">Remarks / Reference</label>
                        <input type="text" name="remarks" id="remarks" class="form-control bg-white text-dark" placeholder="e.g. Stock replenishment, lock order #102">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveAdjustBtn" class="btn btn-primary">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    let table;
    const canAdjust = {{ hasPermission('inventory.adjust') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#inventoryTable').DataTable({
            ajax: {
                url: "{{ route('inventory.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'sku', className: 'align-middle font-weight-bold' },
                { data: 'product_name', className: 'align-middle' },
                { data: 'available_qty', className: 'align-middle text-right' },
                { data: 'reserved_qty', className: 'align-middle text-right' },
                { data: 'sold_qty', className: 'align-middle text-right' },
                { data: 'current_qty', className: 'align-middle text-right font-weight-bold' },
                { data: 'min_stock', className: 'align-middle text-right text-muted' },
                { data: 'max_stock', className: 'align-middle text-right text-muted' },
                {
                    data: null,
                    className: 'text-center align-middle',
                    render: function (data) {
                        let avail = parseFloat(data.available_qty);
                        let min = parseFloat(data.min_stock);
                        
                        if (avail <= 0) {
                            return `<span class="badge badge-danger">Out of Stock</span>`;
                        } else if (avail <= min) {
                            return `<span class="badge badge-warning text-dark">Low Stock</span>`;
                        }
                        return `<span class="badge badge-success">Good</span>`;
                    }
                },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = data === 'active' ? 'badge-success' : 'badge-secondary';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                @if(hasPermission('inventory.adjust'))
                {
                    data: null,
                    className: 'text-center align-middle',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `
                            <button onclick="quickAdjust(${data.id}, ${data.product_id})" class="btn btn-outline-primary btn-sm" title="Quick Adjust" style="padding: 0.25rem 0.5rem;">
                                <i class="mdi mdi-plus-minus-box" style="font-size: 1.2rem; vertical-align: middle;"></i>
                            </button>
                        `;
                    }
                }
                @endif
            ],
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "language": {
                "search": "",
                "searchPlaceholder": "Search inventory..."
            }
        });

        $('#adjustForm').on('submit', function (e) {
            e.preventDefault();
            let invId = $('#inventoryId').val();
            
            // If we don't have inventoryId, resolve it from product selection
            if (!invId) {
                invId = $('#productSelect option:selected').data('inventory-id');
            }

            if (!invId) {
                Swal.fire('Error', 'Unable to resolve inventory record.', 'error');
                return;
            }

            let saveBtn = $('#saveAdjustBtn');
            saveBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: `{{ url('admin/inventory/adjust') }}/${invId}`,
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $('#adjustModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Stock Adjusted',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    });
                    table.ajax.reload(null, false);
                    saveBtn.prop('disabled', false).text('Save Adjustment');
                    $('#adjustForm')[0].reset();
                },
                error: function (xhr) {
                    saveBtn.prop('disabled', false).text('Save Adjustment');
                    Swal.fire({
                        icon: 'error',
                        title: 'Adjustment Failed',
                        text: xhr.responseJSON.message || 'An error occurred during adjustment.',
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });

    function openAdjustmentModal() {
        $('#adjustForm')[0].reset();
        $('#inventoryId').val('');
        $('#productSelect').prop('disabled', false);
        $('#adjustModalTitle').text('New Stock Adjustment');
        $('#adjustModal').modal('show');
    }

    function quickAdjust(inventoryId, productId) {
        $('#adjustForm')[0].reset();
        $('#inventoryId').val(inventoryId);
        $('#productSelect').val(productId).prop('disabled', true);
        $('#adjustModalTitle').text('Quick Stock Adjustment');
        $('#adjustModal').modal('show');
    }
</script>

<style>
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: #212529 !important;
        font-size: 0.875rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ced4da;
        background-color: #ffffff;
        color: #212529;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #3f50f6 !important;
        color: white !important;
        border: 1px solid #3f50f6 !important;
    }
</style>
@endpush
