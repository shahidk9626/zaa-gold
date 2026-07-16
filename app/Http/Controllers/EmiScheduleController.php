<?php

namespace App\Http\Controllers;

use App\Models\BookingEmiSchedule;
use App\Models\GoldBooking;
use App\Models\User;
use Illuminate\Http\Request;

class EmiScheduleController extends Controller
{
    /**
     * Show EMI Schedules listing
     */
    public function index(Request $request)
    {
        $query = BookingEmiSchedule::with(['booking.customer', 'booking.product'])->latest('due_date');

        // 1. Filter by Search Query (Booking Number, Customer Name/Email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($bq) use ($search) {
                    $bq->where('booking_number', 'like', '%' . $search . '%')
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('name', 'like', '%' . $search . '%')
                              ->orWhere('email', 'like', '%' . $search . '%');
                       });
                });
            });
        }

        // 2. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by Customer ID
        if ($request->filled('customer_id')) {
            $query->whereHas('booking', function ($bq) use ($request) {
                $bq->where('customer_id', $request->customer_id);
            });
        }

        // 4. Filter by Booking ID
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // 5. Filter by Due Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        $schedules = $query->paginate(20)->withQueryString();
        $customers = User::where('role_id', 4)->orderBy('name')->get(); // Role 4 = Customer
        $bookings = GoldBooking::orderBy('booking_number')->get();

        return view('admin.emi_schedules.index', compact('schedules', 'customers', 'bookings'));
    }

    /**
     * Export EMI Schedules as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = BookingEmiSchedule::with(['booking.customer', 'booking.product'])->latest('due_date');

        // Apply same filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($bq) use ($search) {
                    $bq->where('booking_number', 'like', '%' . $search . '%')
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('name', 'like', '%' . $search . '%');
                       });
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->whereHas('booking', function ($bq) use ($request) {
                $bq->where('customer_id', $request->customer_id);
            });
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        $schedules = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=EMI_Schedules_Report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Booking Number', 'Customer Name', 'Installment #', 'Due Date', 'Opening Principal (₹)', 'EMI Amount (₹)', 'Principal (₹)', 'Interest (₹)', 'Late Fee (₹)', 'Closing Principal (₹)', 'Status', 'Paid At'];

        $callback = function() use($schedules, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($schedules as $row) {
                fputcsv($file, [
                    $row->booking->booking_number,
                    $row->booking->customer->name ?? 'N/A',
                    $row->installment_number,
                    $row->due_date->format('Y-m-d'),
                    number_format($row->opening_principal, 2, '.', ''),
                    number_format($row->emi_amount, 2, '.', ''),
                    number_format($row->principal_amount, 2, '.', ''),
                    number_format($row->interest_amount, 2, '.', ''),
                    number_format($row->late_fee, 2, '.', ''),
                    number_format($row->closing_principal, 2, '.', ''),
                    $row->status,
                    $row->paid_at ? $row->paid_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
