@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Stock Ledger Transaction Logs</h4>
                        <p class="card-description text-muted">Complete audit trail of all inventory movements</p>
                    </div>
                    <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Inventory
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="transactionTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Type</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Old Stock</th>
                                <th class="text-right">New Stock</th>
                                <th>Remarks</th>
                                <th>Actor</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#transactionTable').DataTable({
            ajax: {
                url: "{{ route('inventory.transactions') }}",
                type: 'GET'
            },
            columns: [
                { data: 'created_at', className: 'align-middle font-weight-bold' },
                { data: 'sku', className: 'align-middle font-weight-bold' },
                { data: 'product_name', className: 'align-middle' },
                { 
                    data: 'type', 
                    className: 'align-middle',
                    render: function (data) {
                        let badgeClass = 'badge-secondary';
                        if (data === 'Purchase') badgeClass = 'badge-success';
                        if (data === 'Reserve') badgeClass = 'badge-warning text-dark';
                        if (data === 'Release') badgeClass = 'badge-info';
                        if (data === 'Sale') badgeClass = 'badge-primary';
                        if (data === 'Adjustment') badgeClass = 'badge-danger';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                { data: 'quantity', className: 'align-middle text-right font-weight-bold' },
                { data: 'old_qty', className: 'align-middle text-right text-muted' },
                { data: 'new_qty', className: 'align-middle text-right font-weight-bold' },
                { data: 'remarks', className: 'align-middle' },
                { data: 'created_by', className: 'align-middle' }
            ],
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "language": {
                "search": "",
                "searchPlaceholder": "Search ledger logs..."
            }
        });
    });
</script>
@endpush
