@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Gold Price Updates Audit History</h4>
                        <p class="card-description text-muted">Complete audit log of gold price revisions and updates</p>
                    </div>
                    <a href="{{ route('gold-prices.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Config
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="historyTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Gold Type</th>
                                <th>Old Rate (per g)</th>
                                <th>New Rate (per g)</th>
                                <th>Price Difference</th>
                                <th>Source / Remarks</th>
                                <th>Updated By</th>
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
        $('#historyTable').DataTable({
            ajax: {
                url: "{{ route('gold-prices.history') }}",
                type: 'GET'
            },
            columns: [
                { data: 'updated_at', className: 'align-middle font-weight-bold' },
                { 
                    data: 'gold_type', 
                    className: 'align-middle',
                    render: function (data) {
                        return `<span class="badge badge-warning text-dark font-weight-bold">${data}</span>`;
                    }
                },
                { data: 'old_price', className: 'align-middle', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
                { data: 'new_price', className: 'align-middle', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
                {
                    data: null,
                    className: 'align-middle font-weight-bold',
                    render: function (data) {
                        let diff = parseFloat(data.new_price) - parseFloat(data.old_price);
                        let textClass = diff >= 0 ? 'text-success' : 'text-danger';
                        let prefix = diff >= 0 ? '+' : '';
                        return `<span class="${textClass}">${prefix}${diff.toFixed(2)}</span>`;
                    }
                },
                { data: 'remarks', className: 'align-middle' },
                { data: 'updated_by', className: 'align-middle' }
            ],
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "language": {
                "search": "",
                "searchPlaceholder": "Search price audits..."
            }
        });
    });
</script>
@endpush
