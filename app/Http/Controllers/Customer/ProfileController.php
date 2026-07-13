<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends CustomerBaseController
{
    public function index(): View
    {
        $user = Auth::user()->load(['customerDetail.documents', 'role']);

        return view('customer.profile.index', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'phone' => 'nullable|string|max:15',
            'whatsapp_number' => 'nullable|string|max:15',
            'alternate_number' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'nominee_name' => 'nullable|string|max:100',
        ]);

        $user->update($request->only(['phone', 'whatsapp_number']));

        if ($user->customerDetail) {
            $user->customerDetail->update($request->only([
                'alternate_number', 'address', 'city', 'state', 'pincode', 'nominee_name',
            ]));
        }

        return back()->with('success', 'Profile updated successfully.');
    }
}
