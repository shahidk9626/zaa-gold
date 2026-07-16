<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerDetail;
use App\Models\CustomerDocument;
use App\Models\Role;
use App\Models\StaffDetail;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Admin Side: Customer Listing
     */
    public function index(Request $request)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        if ($request->ajax()) {
            $users = User::with(['customerDetail', 'referredBy.staffDetail'])
                ->where('role_id', $customerRoleId)
                ->get();

            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'N/A',
                    'whatsapp' => $user->whatsapp_number ?? 'N/A',
                    'referral' => $user->referredBy ? ($user->referredBy->name . ' (' . ($user->referredBy->staffDetail->emp_code ?? 'N/A') . ')') : 'None',
                    'verified' => $user->verification_status ?? 'pending',
                    'profile_complete' => $user->profile_completed ? 'Yes' : 'No',
                    'status' => $user->status,
                    'slug' => $user->customerDetail->slug ?? '',
                ];
            });
            return response()->json(['data' => $data]);
        }
        return view('admin.customers.index');
    }

    /**
     * Admin Side: View Customer Profile
     */
    public function show($id)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $customer = User::with(['customerDetail', 'customerDocuments', 'referredBy.staffDetail'])
            ->where('role_id', $customerRoleId)
            ->findOrFail($id);

        // Calculate purchase limit stats
        $bookingService = app(\App\Services\BookingService::class);
        $limit = (float) \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);
        $purchased = $bookingService->getPurchasedWeightForFinancialYear($customer->id);
        $remaining = $bookingService->getRemainingPurchaseLimit($customer->id);
        $percentage = $limit > 0 ? ($purchased / $limit) * 100 : 0;

        $purchaseLimit = [
            'limit' => $limit,
            'purchased' => $purchased,
            'remaining' => $remaining,
            'percentage' => $percentage,
        ];

        // Next/Previous Navigation
        $prev = User::where('role_id', $customerRoleId)->where('id', '<', $id)->orderBy('id', 'desc')->first();
        $next = User::where('role_id', $customerRoleId)->where('id', '>', $id)->orderBy('id', 'asc')->first();

        return view('admin.customers.show', compact('customer', 'prev', 'next', 'purchaseLimit'));
    }

    /**
     * Admin Side: Create View
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Admin Side: Store Customer (Wizard Form)
     */
    public function store(StoreCustomerRequest $request)
    {
        try {
            DB::beginTransaction();

            $customerRole = Role::where('slug', 'customer')->first();
            $customerRoleId = $customerRole ? $customerRole->id : 0;

            $referredById = null;
            if ($request->referral_code) {
                $staff = StaffDetail::where('emp_code', $request->referral_code)->first();
                if ($staff) {
                    $referredById = $staff->user_id;
                }
            }

            // Create User Identity
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'whatsapp_number' => $request->whatsapp_number,
                'password' => Hash::make(Str::random(12)),
                'role_id' => $customerRoleId,
                'referred_by_staff_id' => $referredById,
                'status' => $request->status ? 'active' : 'inactive',
                'profile_completed' => 1, // Created via admin wizard, complete profile
                'verification_status' => 'verified',
            ]);

            // Create Customer Business Details
            $slug = Str::slug($user->name . '-' . Str::random(5));
            $customerDetail = CustomerDetail::create(array_merge($request->validated(), [
                'user_id' => $user->id,
                'slug' => $slug,
            ]));

            // Handle Documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    if ($file) {
                        $docName = $request->document_names[$key] ?? $file->getClientOriginalName();
                        $path = $file->store('customer_docs', 'public');

                        CustomerDocument::create([
                            'customer_detail_id' => $customerDetail->id,
                            'document_name' => $docName,
                            'file_path' => $path,
                            'file_original_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientMimeType(),
                        ]);
                    }
                }
            }

            DB::commit();

            // Send Welcome Email (Post-Commit to ensure data integrity)
            if ($user->email) {
                try {
                    $user->load('customerDetail');
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->send(new \App\Mail\WelcomeCustomerMail($user));
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::error("Customer Welcome Email Failed: " . $mailEx->getMessage());
                }
            }

            return response()->json(['success' => 'Customer created successfully', 'user' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin Side: Edit View
     */
    public function edit($id)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $customer = User::with(['customerDetail', 'customerDocuments', 'referredBy.staffDetail'])
            ->where('role_id', $customerRoleId)
            ->findOrFail($id);

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Admin Side: Update Customer (Wizard Form)
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $user = User::with('customerDetail')
            ->where('role_id', $customerRoleId)
            ->findOrFail($id);

        $customerDetail = $user->customerDetail;

        try {
            DB::beginTransaction();

            $referredById = $user->referred_by_staff_id;
            if ($request->has('referral_code')) {
                if ($request->referral_code) {
                    $staff = StaffDetail::where('emp_code', $request->referral_code)->first();
                    if ($staff) {
                        $referredById = $staff->user_id;
                    }
                } else {
                    $referredById = null;
                }
            }

            // Update User Identity
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'whatsapp_number' => $request->whatsapp_number,
                'status' => $request->status ? 'active' : 'inactive',
                'referred_by_staff_id' => $referredById,
            ]);

            // Update Customer Business Details
            if ($customerDetail) {
                $customerDetail->update($request->validated());
            } else {
                $slug = Str::slug($user->name . '-' . Str::random(5));
                $customerDetail = CustomerDetail::create(array_merge($request->validated(), [
                    'user_id' => $user->id,
                    'slug' => $slug,
                ]));
            }

            // Handle New Documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    if ($file) {
                        $docName = $request->document_names[$key] ?? $file->getClientOriginalName();
                        $path = $file->store('customer_docs', 'public');

                        CustomerDocument::create([
                            'customer_detail_id' => $customerDetail->id,
                            'document_name' => $docName,
                            'file_path' => $path,
                            'file_original_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientMimeType(),
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => 'Customer updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin Side: Verify Customer
     */
    public function verify($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->verification_status = 'verified';
            $user->status = 'active';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin Side: Toggle status
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    /**
     * Admin Side: Delete specific document
     */
    public function deleteDocument($id)
    {
        $doc = CustomerDocument::findOrFail($id);
        Storage::disk('public')->delete($doc->file_path);
        $doc->delete();
        return response()->json(['success' => 'Document deleted successfully']);
    }

    /**
     * Admin Side: Destroy Customer
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $hasDependencies = false;

        if (class_exists('\App\Models\PurchasedPlan')) {
            $hasDependencies = $hasDependencies || \App\Models\PurchasedPlan::where('user_id', $id)->exists();
        }
        if (class_exists('\App\Models\Transaction')) {
            $hasDependencies = $hasDependencies || \App\Models\Transaction::where('user_id', $id)->exists();
        }

        if ($hasDependencies) {
            return response()->json([
                'error' => 'This customer cannot be deleted because they have active purchased plans or transactions.'
            ]);
        }

        if ($user->customerDetail) {
            $user->customerDetail->delete();
        }
        $user->delete();

        return response()->json(['success' => 'Customer deleted successfully']);
    }

    /**
     * Admin Side: Bulk Destroy Customers
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $ids = $request->ids;
        $totalSelected = count($ids);
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($ids as $id) {
            $hasDependencies = false;
            if (class_exists('\App\Models\PurchasedPlan')) {
                $hasDependencies = $hasDependencies || \App\Models\PurchasedPlan::where('user_id', $id)->exists();
            }
            if (class_exists('\App\Models\Transaction')) {
                $hasDependencies = $hasDependencies || \App\Models\Transaction::where('user_id', $id)->exists();
            }

            if ($hasDependencies) {
                $skippedCount++;
            } else {
                $user = User::find($id);
                if ($user) {
                    if ($user->customerDetail) {
                        $user->customerDetail->delete();
                    }
                    $user->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'selected' => $totalSelected,
                'deleted' => $deletedCount,
                'skipped' => $skippedCount,
                'message' => "{$totalSelected} selected\n{$deletedCount} deleted\n{$skippedCount} skipped because of active plans or transactions."
            ]
        ]);
    }

    /**
     * Admin Side: Export Customers
     */
    public function export(Request $request)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }

        $query = User::with(['customerDetail', 'referredBy.staffDetail'])
            ->where('role_id', $customerRoleId)
            ->latest();

        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }

        $customers = $query->get();

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            // CSV Fallback
            $fileName = 'Customers_Export_' . date('Y-m-d') . '.csv';
            return response()->streamDownload(function() use ($customers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'ID', 'Name', 'Email', 'Phone', 'WhatsApp', 'Referral Code', 
                    'Father Name', 'Mother Name', 'Nominee Name', 'DOB', 'Gender', 
                    'Marital Status', 'Alternate Number', 'Address', 'City', 'State', 
                    'Country', 'Pincode', 'Occupation', 'Annual Income', 'Bank Name', 
                    'Account Number', 'IFSC Code', 'Branch', 'PAN Number', 'Aadhar Number', 
                    'Status', 'Verification Status'
                ]);

                foreach ($customers as $c) {
                    $d = $c->customerDetail;
                    fputcsv($file, [
                        $c->id,
                        $c->name,
                        $c->email,
                        $c->phone ?? '',
                        $c->whatsapp_number ?? '',
                        $c->referredBy->staffDetail->emp_code ?? '',
                        $d->father_name ?? '',
                        $d->mother_name ?? '',
                        $d->nominee_name ?? '',
                        $d->dob ?? '',
                        $d->gender ?? '',
                        $d->marital_status ?? '',
                        $d->alternate_number ?? '',
                        $d->address ?? '',
                        $d->city ?? '',
                        $d->state ?? '',
                        $d->country ?? '',
                        $d->pincode ?? '',
                        $d->occupation ?? '',
                        $d->annual_income ?? '',
                        $d->bank_name ?? '',
                        $d->account_number ?? '',
                        $d->ifsc_code ?? '',
                        $d->branch ?? '',
                        $d->pan_number ?? '',
                        $d->aadhar_number ?? '',
                        $c->status,
                        $c->verification_status ?? 'pending'
                    ]);
                }
                fclose($file);
            }, $fileName, [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        // PhpSpreadsheet Implementation
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'ID', 'B1' => 'Name', 'C1' => 'Email', 'D1' => 'Phone', 'E1' => 'WhatsApp', 
            'F1' => 'Referral Code', 'G1' => 'Father Name', 'H1' => 'Mother Name', 'I1' => 'Nominee Name', 
            'J1' => 'DOB', 'K1' => 'Gender', 'L1' => 'Marital Status', 'M1' => 'Alternate Number', 
            'N1' => 'Address', 'O1' => 'City', 'P1' => 'State', 'Q1' => 'Country', 'R1' => 'Pincode', 
            'S1' => 'Occupation', 'T1' => 'Annual Income', 'U1' => 'Bank Name', 'V1' => 'Account Number', 
            'W1' => 'IFSC Code', 'X1' => 'Branch', 'Y1' => 'PAN Number', 'Z1' => 'Aadhar Number', 
            'AA1' => 'Status', 'AB1' => 'Verification Status'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF3F50F6'],
            ],
        ];
        $sheet->getStyle('A1:AB1')->applyFromArray($headerStyle);

        $rowNumber = 2;
        foreach ($customers as $c) {
            $d = $c->customerDetail;
            $sheet->setCellValue('A' . $rowNumber, $c->id);
            $sheet->setCellValue('B' . $rowNumber, $c->name);
            $sheet->setCellValue('C' . $rowNumber, $c->email);
            $sheet->setCellValue('D' . $rowNumber, $c->phone ?? '');
            $sheet->setCellValue('E' . $rowNumber, $c->whatsapp_number ?? '');
            $sheet->setCellValue('F' . $rowNumber, $c->referredBy->staffDetail->emp_code ?? '');
            $sheet->setCellValue('G' . $rowNumber, $d->father_name ?? '');
            $sheet->setCellValue('H' . $rowNumber, $d->mother_name ?? '');
            $sheet->setCellValue('I' . $rowNumber, $d->nominee_name ?? '');
            $sheet->setCellValue('J' . $rowNumber, $d->dob ?? '');
            $sheet->setCellValue('K' . $rowNumber, $d->gender ?? '');
            $sheet->setCellValue('L' . $rowNumber, $d->marital_status ?? '');
            $sheet->setCellValue('M' . $rowNumber, $d->alternate_number ?? '');
            $sheet->setCellValue('N' . $rowNumber, $d->address ?? '');
            $sheet->setCellValue('O' . $rowNumber, $d->city ?? '');
            $sheet->setCellValue('P' . $rowNumber, $d->state ?? '');
            $sheet->setCellValue('Q' . $rowNumber, $d->country ?? '');
            $sheet->setCellValue('R' . $rowNumber, $d->pincode ?? '');
            $sheet->setCellValue('S' . $rowNumber, $d->occupation ?? '');
            $sheet->setCellValue('T' . $rowNumber, $d->annual_income ?? '');
            $sheet->setCellValue('U' . $rowNumber, $d->bank_name ?? '');
            $sheet->setCellValue('V' . $rowNumber, $d->account_number ?? '');
            $sheet->setCellValue('W' . $rowNumber, $d->ifsc_code ?? '');
            $sheet->setCellValue('X' . $rowNumber, $d->branch ?? '');
            $sheet->setCellValue('Y' . $rowNumber, $d->pan_number ?? '');
            $sheet->setCellValue('Z' . $rowNumber, $d->aadhar_number ?? '');
            $sheet->setCellValue('AA' . $rowNumber, ucfirst($c->status));
            $sheet->setCellValue('AB' . $rowNumber, ucfirst($c->verification_status ?? 'pending'));

            $rowNumber++;
        }

        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('AA')->setAutoSize(true);
        $sheet->getColumnDimension('AB')->setAutoSize(true);

        $fileName = 'Customers_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Admin Side: Download Template for Customer Import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Name', 'Email', 'Phone', 'WhatsApp Number', 'Referral Code', 
            'Father Name', 'Mother Name', 'Nominee Name', 'DOB (YYYY-MM-DD)', 'Gender (Male/Female/Other)', 
            'Marital Status', 'Alternate Number', 'Address', 'City', 'State', 
            'Country', 'Pincode', 'Occupation', 'Annual Income', 'Bank Name', 
            'Account Number', 'IFSC Code', 'Branch', 'PAN Number', 'Aadhar Number'
        ];

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            // CSV Template
            return response()->streamDownload(function() use ($headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $headers);
                fputcsv($file, [
                    'John Doe', 'john@example.com', '9876543210', '9876543210', 'EMP-1',
                    'Richard Doe', 'Jane Doe', 'Mary Doe', '1990-05-15', 'Male',
                    'Single', '', '123 Main St', 'Bengaluru', 'Karnataka',
                    'India', '560001', 'Engineer', '600000', 'State Bank of India',
                    '12345678901', 'SBIN0000001', 'Main Branch', 'ABCDE1234F', '123456789012'
                ]);
                fclose($file);
            }, 'customers_import_template.csv', [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');

        $sampleRow = [
            'John Doe', 'john@example.com', '9876543210', '9876543210', 'EMP-1',
            'Richard Doe', 'Jane Doe', 'Mary Doe', '1990-05-15', 'Male',
            'Single', '', '123 Main St', 'Bengaluru', 'Karnataka',
            'India', '560001', 'Engineer', '600000', 'State Bank of India',
            '12345678901', 'SBIN0000001', 'Main Branch', 'ABCDE1234F', '123456789012'
        ];
        $sheet->fromArray($sampleRow, null, 'A2');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'customers_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Admin Side: Import Customers
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('file');
        $rows = [];

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class) || $file->getClientOriginalExtension() === 'csv') {
            // Read CSV manually
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
        } else {
            // Use PhpSpreadsheet
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid spreadsheet file: ' . $e->getMessage()], 422);
            }
        }

        if (count($rows) <= 1) {
            return response()->json(['error' => 'The uploaded file has no data rows.'], 422);
        }

        // Remove header row
        array_shift($rows);

        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        $successCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (empty($row) || !isset($row[0]) || trim($row[0]) === '') {
                    continue; // Skip empty rows
                }

                $name = trim($row[0]);
                $email = isset($row[1]) ? trim($row[1]) : '';
                $phone = isset($row[2]) ? trim($row[2]) : '';
                $whatsapp = isset($row[3]) ? trim($row[3]) : '';
                $refCode = isset($row[4]) ? trim($row[4]) : null;

                // Validate basic info
                if (empty($email)) {
                    $errors[] = "Row " . ($index + 2) . ": Email is required.";
                    continue;
                }

                if (User::where('email', $email)->exists()) {
                    $errors[] = "Row " . ($index + 2) . ": Email {$email} already exists.";
                    continue;
                }

                $referredById = null;
                if ($refCode) {
                    $staff = StaffDetail::where('emp_code', $refCode)->first();
                    if ($staff) {
                        $referredById = $staff->user_id;
                    }
                }

                // Create User
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'whatsapp_number' => $whatsapp ?: null,
                    'password' => Hash::make(Str::random(12)),
                    'role_id' => $customerRoleId,
                    'referred_by_staff_id' => $referredById,
                    'status' => 'active',
                    'profile_completed' => 1,
                    'verification_status' => 'verified',
                ]);

                // Create details
                $slug = Str::slug($name . '-' . Str::random(5));
                CustomerDetail::create([
                    'user_id' => $user->id,
                    'father_name' => isset($row[5]) ? trim($row[5]) : null,
                    'mother_name' => isset($row[6]) ? trim($row[6]) : null,
                    'nominee_name' => isset($row[7]) ? trim($row[7]) : null,
                    'dob' => isset($row[8]) && !empty(trim($row[8])) ? trim($row[8]) : null,
                    'gender' => isset($row[9]) ? trim($row[9]) : null,
                    'marital_status' => isset($row[10]) ? trim($row[10]) : null,
                    'alternate_number' => isset($row[11]) ? trim($row[11]) : null,
                    'address' => isset($row[12]) ? trim($row[12]) : null,
                    'city' => isset($row[13]) ? trim($row[13]) : null,
                    'state' => isset($row[14]) ? trim($row[14]) : null,
                    'country' => isset($row[15]) ? trim($row[15]) : null,
                    'pincode' => isset($row[16]) ? trim($row[16]) : null,
                    'occupation' => isset($row[17]) ? trim($row[17]) : null,
                    'annual_income' => isset($row[18]) ? trim($row[18]) : null,
                    'bank_name' => isset($row[19]) ? trim($row[19]) : null,
                    'account_number' => isset($row[20]) ? trim($row[20]) : null,
                    'ifsc_code' => isset($row[21]) ? trim($row[21]) : null,
                    'branch' => isset($row[22]) ? trim($row[22]) : null,
                    'pan_number' => isset($row[23]) ? trim($row[23]) : null,
                    'aadhar_number' => isset($row[24]) ? trim($row[24]) : null,
                    'slug' => $slug,
                ]);

                $successCount++;
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return response()->json(['errors' => $errors], 422);
            }

            DB::commit();
            return response()->json(['success' => "{$successCount} customers imported successfully."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during import: ' . $e->getMessage()], 500);
        }
    }
}
