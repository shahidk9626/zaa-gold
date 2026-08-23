<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends CustomerBaseController
{
    public function index(): View
    {
        return view('customer.support.index');
    }

    public function storeTicket(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Support ticket submitted successfully.',
            ]);
        }

        return back()->with('ticket_submitted', true);
    }
}
