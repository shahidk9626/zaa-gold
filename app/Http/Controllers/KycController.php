<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreKycRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $kycs = Kyc::with('user')->latest()->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'customer_name' => $item->user->name ?? 'N/A',
                    'customer_email' => $item->user->email ?? 'N/A',
                    'document_type' => $item->document_type,
                    'document_number' => $item->document_number,
                    'status' => $item->status,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });
            return response()->json(['data' => $kycs]);
        }
        return view('admin.kyc.index');
    }

    public function create()
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $customers = User::where('role_id', $customerRoleId)->get();
        return view('admin.kyc.create', compact('customers'));
    }

    public function store(StoreKycRequest $request)
    {
        $data = $request->validated();
        $data['status'] = 'pending';

        if ($request->hasFile('front_image')) {
            $data['front_image'] = $request->file('front_image')->store('kyc/front', 'public');
        }
        if ($request->hasFile('back_image')) {
            $data['back_image'] = $request->file('back_image')->store('kyc/back', 'public');
        }
        if ($request->hasFile('selfie')) {
            $data['selfie'] = $request->file('selfie')->store('kyc/selfie', 'public');
        }

        $kyc = Kyc::create($data);

        // Update user status
        $user = User::find($request->user_id);
        if ($user) {
            $user->verification_status = 'pending';
            $user->save();
        }

        return response()->json(['success' => 'KYC submitted successfully and is pending review.', 'kyc' => $kyc]);
    }

    public function show($id)
    {
        $kyc = Kyc::with(['user', 'approver'])->findOrFail($id);
        return view('admin.kyc.show', compact('kyc'));
    }

    public function edit($id)
    {
        $kyc = Kyc::findOrFail($id);
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;
        $customers = User::where('role_id', $customerRoleId)->get();

        return view('admin.kyc.edit', compact('kyc', 'customers'));
    }

    public function update(StoreKycRequest $request, $id)
    {
        $kyc = Kyc::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('front_image')) {
            if ($kyc->front_image) Storage::disk('public')->delete($kyc->front_image);
            $data['front_image'] = $request->file('front_image')->store('kyc/front', 'public');
        }
        if ($request->hasFile('back_image')) {
            if ($kyc->back_image) Storage::disk('public')->delete($kyc->back_image);
            $data['back_image'] = $request->file('back_image')->store('kyc/back', 'public');
        }
        if ($request->hasFile('selfie')) {
            if ($kyc->selfie) Storage::disk('public')->delete($kyc->selfie);
            $data['selfie'] = $request->file('selfie')->store('kyc/selfie', 'public');
        }

        $kyc->update($data);

        return response()->json(['success' => 'KYC records updated successfully.']);
    }

    public function approve($id)
    {
        $kyc = Kyc::findOrFail($id);
        $kyc->status = 'approved';
        $kyc->approved_by = auth()->id();
        $kyc->approved_at = now();
        $kyc->rejected_reason = null;
        $kyc->save();

        $user = $kyc->user;
        if ($user) {
            $user->verification_status = 'verified';
            $user->save();
        }

        return response()->json(['success' => 'KYC application has been approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:255',
        ]);

        $kyc = Kyc::findOrFail($id);
        $kyc->status = 'rejected';
        $kyc->approved_by = auth()->id();
        $kyc->approved_at = now();
        $kyc->rejected_reason = $request->rejected_reason;
        $kyc->save();

        $user = $kyc->user;
        if ($user) {
            $user->verification_status = 'rejected';
            $user->save();
        }

        return response()->json(['success' => 'KYC application has been rejected successfully.']);
    }

    public function destroy($id)
    {
        $kyc = Kyc::findOrFail($id);

        if ($kyc->front_image) Storage::disk('public')->delete($kyc->front_image);
        if ($kyc->back_image) Storage::disk('public')->delete($kyc->back_image);
        if ($kyc->selfie) Storage::disk('public')->delete($kyc->selfie);

        $kyc->delete();

        return response()->json(['success' => 'KYC record deleted successfully.']);
    }

    public function download($id)
    {
        $kyc = Kyc::findOrFail($id);
        $files = [];

        if ($kyc->front_image) {
            $files['front_' . basename($kyc->front_image)] = storage_path('app/public/' . $kyc->front_image);
        }
        if ($kyc->back_image) {
            $files['back_' . basename($kyc->back_image)] = storage_path('app/public/' . $kyc->back_image);
        }
        if ($kyc->selfie) {
            $files['selfie_' . basename($kyc->selfie)] = storage_path('app/public/' . $kyc->selfie);
        }

        if (class_exists(\ZipArchive::class) && count($files) > 0) {
            $zip = new \ZipArchive();
            $zipFileName = 'KYC_Documents_' . $kyc->id . '.zip';
            $zipPath = storage_path('app/' . $zipFileName);

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($files as $name => $path) {
                    if (file_exists($path)) {
                        $zip->addFile($path, $name);
                    }
                }
                $zip->close();

                if (file_exists($zipPath)) {
                    return response()->download($zipPath)->deleteFileAfterSend(true);
                }
            }
        }

        // Fallback: download front image if ZIP creation fails
        if ($kyc->front_image && file_exists(storage_path('app/public/' . $kyc->front_image))) {
            return response()->download(storage_path('app/public/' . $kyc->front_image));
        }

        return redirect()->back()->with('error', 'Documents files could not be downloaded.');
    }
}
