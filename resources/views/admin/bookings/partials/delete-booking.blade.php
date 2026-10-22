{{-- Super Admin booking delete: shared hidden form + confirmation dialog --}}
<form id="deleteBookingForm" method="POST" action="" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function confirmDeleteBooking(deleteUrl, bookingNumber, status) {
        const content = $('<div class="text-left"></div>');
        $('<p class="mb-2"></p>').append('Booking: ', $('<strong></strong>').text(bookingNumber), ' (', document.createTextNode(status), ')').appendTo(content);
        $('<p class="mb-2"></p>').text('Deleting this booking will soft-delete its EMAP schedule, receipts, GST invoices, price lock certificate, delivery requests, cancellation requests and booking referral records.').appendTo(content);
        $('<p class="mb-0 text-muted small"></p>').text('Payment gateway transactions, cash collection records, status history and audit logs are retained. No refund or gateway cancellation is performed.').appendTo(content);

        Swal.fire({
            title: 'Delete Booking?',
            html: content[0],
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Delete Booking',
            cancelButtonText: 'Cancel',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteBookingForm');
                form.action = deleteUrl;
                form.submit();
            }
        });
    }
</script>
