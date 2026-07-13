<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;

abstract class CustomerBaseController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    protected function customerId(): int
    {
        return Auth::id();
    }
}
