<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

class SupportController extends CustomerBaseController
{
    public function index(): View
    {
        return view('customer.support.index');
    }
}
