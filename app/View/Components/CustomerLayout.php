<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class CustomerLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $pageTitle = null,
    ) {}

    public function render(): View
    {
        return view('customer.layouts.app');
    }
}
