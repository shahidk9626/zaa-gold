<!-- Step 3: Financial Summary Card (Initially Hidden) -->
<div class="row text-dark" id="summaryRow" style="display: none;">
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4" style="border-top: 4px solid #3f50f6 !important;">
            <h5 class="text-primary font-weight-bold mb-4 border-bottom pb-2">Complete Financial Summary</h5>
            
            <div class="row">
                @if($showCustomer ?? true)
                <div class="col-md-4 mb-3" id="sumCustomerNameBlock">
                    <label class="small text-muted d-block uppercase mb-1">Customer / Nominee Partner</label>
                    <span class="font-weight-bold text-dark" id="sumCustomerName">-</span>
                </div>
                @endif
                <div class="col-md-4 mb-3">
                    <label class="small text-muted d-block uppercase mb-1">Product Specifications</label>
                    <span class="font-weight-bold text-dark" id="sumProductSpecs">-</span>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="small text-muted d-block uppercase mb-1">Selected EMI Plan</label>
                    <span class="font-weight-bold text-dark" id="sumPlanName">-</span>
                </div>
            </div>

            <hr class="my-3">

            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1">Live Gold Price Per Gram</label>
                    <span class="font-weight-bold text-dark" id="sumGoldRate">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1">Base Gold/Product Value</label>
                    <span class="font-weight-bold text-dark" id="sumProductPrice">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" id="sumProcessingFeeBlock">
                    <label class="small text-muted d-block mb-1">Calculated Processing Fee</label>
                    <span class="font-weight-bold text-dark" id="sumProcessingFee">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" id="sumInterestBlock">
                    <label class="small text-muted d-block mb-1">Total Plan Interest</label>
                    <span class="font-weight-bold text-dark" id="sumInterest">-</span>
                </div>

                <div class="col-md-3 col-sm-6 mb-3" id="sumGstGoldBlock" style="display:none !important;">
                    <label class="small text-muted d-block mb-1">GST on Gold</label>
                    <span class="font-weight-bold text-dark" id="sumGstGold">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" id="sumFinanceBlock" style="display:none !important;">
                    <label class="small text-muted d-block mb-1">Finance Charge</label>
                    <span class="font-weight-bold text-dark" id="sumFinance">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" id="sumStorageBlock" style="display:none !important;">
                    <label class="small text-muted d-block mb-1">Storage / Insurance / Price Lock</label>
                    <span class="font-weight-bold text-dark" id="sumStorage">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" id="sumGstChargesBlock" style="display:none !important;">
                    <label class="small text-muted d-block mb-1">GST on Charges</label>
                    <span class="font-weight-bold text-dark" id="sumGstCharges">-</span>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1 text-primary font-weight-bold">Monthly Installment (EMI)</label>
                    <span class="font-weight-bold text-primary" id="sumEmiAmount" style="font-size: 1.25rem;">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1 text-success font-weight-bold">Total Estimated Payable</label>
                    <span class="font-weight-bold text-success" id="sumTotalPayable" style="font-size: 1.25rem;">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1">Estimated Completion Date</label>
                    <span class="font-weight-bold text-dark" id="sumCompletionDate">-</span>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="small text-muted d-block mb-1">Default Penalty Standard</label>
                    <span class="font-weight-bold text-danger" id="sumLateFee">-</span>
                </div>
            </div>

            <div class="d-flex justify-content-end border-top mt-4 pt-3">
                <button type="button" id="viewOutstandingBtn" class="btn btn-info px-5 btn-lg mr-2">
                    <i class="mdi mdi-file-document mr-1"></i> View Outstanding Statement
                </button>
                @if($showBooking ?? true)
                <button type="button" id="continueBtn" class="btn btn-primary px-5 btn-lg">
                    <i class="mdi mdi-arrow-right-bold-circle mr-1"></i> Continue to Booking
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Outstanding Statement Modal -->
<div class="modal fade" id="outstandingModal" tabindex="-1" role="dialog" aria-labelledby="outstandingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-primary font-weight-bold" id="outstandingModalLabel">
                    <i class="mdi mdi-file-document-box mr-1"></i> EMI Outstanding Statement (Preview)
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <!-- Outstanding Summary Card -->
                <div class="card border p-3 mb-4 bg-light">
                    <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2 text-uppercase">Outstanding Summary</h6>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Customer Name</label>
                            <span class="font-weight-bold text-dark" id="modalCustomerName">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Product</label>
                            <span class="font-weight-bold text-dark" id="modalProduct">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Gold Weight</label>
                            <span class="font-weight-bold text-dark" id="modalGoldWeight">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Gold Price / Gram</label>
                            <span class="font-weight-bold text-dark" id="modalGoldPrice">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Selected EMI Plan</label>
                            <span class="font-weight-bold text-dark" id="modalPlan">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Duration</label>
                            <span class="font-weight-bold text-dark" id="modalDuration">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Finance Charge</label>
                            <span class="font-weight-bold text-dark" id="modalFinanceCharge">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">Storage Charge</label>
                            <span class="font-weight-bold text-dark" id="modalStorageCharge">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">GST on Gold</label>
                            <span class="font-weight-bold text-dark" id="modalGstGold">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1">GST on Charges</label>
                            <span class="font-weight-bold text-dark" id="modalGstCharges">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1 text-success font-weight-bold">Grand Total</label>
                            <span class="font-weight-bold text-success" id="modalGrandTotal" style="font-size: 1.1rem;">-</span>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="small text-muted d-block uppercase mb-1 text-primary font-weight-bold">Monthly EMI</label>
                            <span class="font-weight-bold text-primary" id="modalMonthlyEmi" style="font-size: 1.1rem;">-</span>
                        </div>
                    </div>
                </div>

                <!-- Repayment Schedule Table -->
                <h6 class="font-weight-bold text-dark mb-3 text-uppercase">EMI Repayment Schedule</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-dark">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Month No.</th>
                                <th>Due Date</th>
                                <th>Opening Principal</th>
                                <th>Principal Amount</th>
                                <th>Interest Amount</th>
                                <th>Monthly EMI</th>
                                <th>Closing Principal</th>
                                <th>Running Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="modalScheduleTableBody">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                @if(hasPermission('emi-outstanding.export'))
                <a href="" id="modalExportPdfBtn" class="btn btn-success px-4">
                    <i class="mdi mdi-file-pdf mr-1"></i> Export PDF
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#viewOutstandingBtn').on('click', function() {
            let customerId = typeof selectedCustomerId !== 'undefined' ? selectedCustomerId : null;
            let productId = typeof selectedProductId !== 'undefined' ? selectedProductId : null;
            let planId = typeof selectedEmiPlanId !== 'undefined' ? selectedEmiPlanId : null;

            if (!productId || !planId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'Please select a product and an EMI plan first.',
                    confirmButtonColor: '#3f50f6'
                });
                return;
            }

            // Loader
            Swal.fire({
                title: 'Loading Schedule...',
                text: 'Calculating your repayment terms dynamically.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ $outstandingUrl }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId,
                    emi_plan_id: planId,
                    customer_id: customerId
                },
                success: function(response) {
                    Swal.close();

                    // Fill modal details
                    $('#modalCustomerName').text(response.customer_name || 'N/A (Calculator Mode)');
                    $('#modalProduct').text(response.product_name + ' (' + response.sku + ')');
                    $('#modalGoldWeight').text(parseFloat(response.weight_in_grams).toFixed(2) + 'g');
                    $('#modalGoldPrice').text('₹' + parseFloat(response.gold_price_per_gram).toLocaleString());
                    $('#modalPlan').text(response.plan_name);
                    $('#modalDuration').text(response.duration_months + ' Months');
                    
                    $('#modalFinanceCharge').text('₹' + parseFloat(response.finance_charge).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#modalStorageCharge').text('₹' + parseFloat(response.storage_charge).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#modalGstGold').text('₹' + parseFloat(response.gst_on_gold).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#modalGstCharges').text('₹' + parseFloat(response.gst_on_charges).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#modalGrandTotal').text('₹' + parseFloat(response.grand_total).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#modalMonthlyEmi').text('₹' + parseFloat(response.monthly_emi).toLocaleString(undefined, {minimumFractionDigits: 2}));

                    // Fill table
                    let tableHtml = '';
                    response.schedule.forEach(row => {
                        tableHtml += `
                            <tr>
                                <td>${row.month_no}</td>
                                <td>${row.due_date}</td>
                                <td>₹${parseFloat(row.opening_principal).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td>₹${parseFloat(row.principal_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td>₹${parseFloat(row.interest_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td>₹${parseFloat(row.monthly_emi).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td>₹${parseFloat(row.closing_principal).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td>₹${parseFloat(row.running_balance).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td><span class="badge badge-warning text-dark font-weight-bold">${row.status}</span></td>
                            </tr>
                        `;
                    });
                    $('#modalScheduleTableBody').html(tableHtml);

                    // Dynamic PDF Link
                    @if(hasPermission('emi-outstanding.export'))
                        let pdfBaseUrl = "{{ $pdfUrl }}";
                        let fullPdfUrl = `${pdfBaseUrl}?product_id=${productId}&emi_plan_id=${planId}`;
                        if (customerId) {
                            fullPdfUrl += `&customer_id=${customerId}`;
                        }
                        $('#modalExportPdfBtn').attr('href', fullPdfUrl);
                    @endif

                    // Show Modal
                    $('#outstandingModal').modal('show');
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Calculation Error',
                        text: xhr.responseJSON.error || 'Failed to generate schedule details.',
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>
@endpush
