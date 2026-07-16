<?php

namespace App\Http\Controllers;

use App\Models\StaffDocument;
use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Models\RolePermission;
use App\Models\StaffDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with(['role', 'staffDetail'])->whereHas('staffDetail')->get();
            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'emp_code' => $user->staffDetail->emp_code ?? 'N/A',
                    'name' => $user->name,
                    'full_name' => $user->staffDetail->full_name ?? $user->name,
                    'role' => [
                        'name' => $user->role->name ?? 'N/A'
                    ],
                    'phone' => $user->phone,
                    'department' => $user->staffDetail->department ?? 'N/A',
                    'status' => $user->status === 'active' ? 1 : 0,
                    'slug' => $user->staffDetail->slug ?? '',
                    'joining_date' => $user->staffDetail->joining_date ?? 'N/A',
                ];
            });
            return response()->json(['data' => $data]);
        }
        return view('staff.index');
    }

    /**
     * Admin Side: View Staff Profile
     */
    public function show($id)
    {
        $staff = User::with(['staffDetail', 'staffDocuments', 'role'])->findOrFail($id);

        // Next/Previous Navigation
        $prev = User::whereHas('staffDetail')->where('id', '<', $id)->orderBy('id', 'desc')->first();
        $next = User::whereHas('staffDetail')->where('id', '>', $id)->orderBy('id', 'asc')->first();

        return view('staff.show', compact('staff', 'prev', 'next'));
    }

    public function create()
    {
        $roles = Role::where('status', 1)->get();
        return view('staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'first_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'joining_date' => 'required|date',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'pincode' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Auto-generate Employee Code
            $count = StaffDetail::withTrashed()->count();
            $empCode = 'EMP-' . ($count + 1);

            // Generate Slug
            $fullName = trim($request->first_name . ' ' . ($request->last_name ?? ''));
            $slug = Str::slug($fullName . '-' . $empCode);

            // Generate Secure Random Password (8-12 chars: upper, lower, num, special)
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $caps = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $nums = '0123456789';
            $syms = '!@#$%^&*';
            
            $plainPassword = substr(str_shuffle($chars), 0, 3) . 
                             substr(str_shuffle($caps), 0, 3) . 
                             substr(str_shuffle($nums), 0, 2) . 
                             substr(str_shuffle($syms), 0, 1);
            $plainPassword = str_shuffle($plainPassword);

            // 1. Create User Identity
            $user = User::create([
                'name' => $fullName,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($plainPassword),
                'role_id' => $request->role_id,
                'status' => $request->status ? 'active' : 'inactive',
            ]);

            // 2. Create Staff Details
            $staffDetail = StaffDetail::create(array_merge($request->all(), [
                'user_id' => $user->id,
                'emp_code' => $empCode,
                'slug' => $slug,
                'created_by' => auth()->id(),
            ]));

            // 3. Handle Documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    $docName = $request->document_names[$key] ?? $file->getClientOriginalName();
                    $path = $file->store('staff_docs', 'public');

                    StaffDocument::create([
                        'user_id' => $user->id,
                        'document_name' => $docName,
                        'file_path' => $path,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();

            // 4. Send Welcome Email (Post-Commit to ensure data integrity)
            if ($user->email) {
                try {
                    $user->load(['role', 'staffDetail']); // Ensure role and staff detail are available in the email
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->cc('shahidkhanasind25@gmail.com')
                        ->send(new \App\Mail\StaffCredentialsMail($user, $plainPassword));
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::error("Staff Credentials Email Failed: " . $mailEx->getMessage());
                }
            }

            return response()->json(['success' => 'Staff created successfully. Credentials have been sent to their email.', 'user' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function edit($slug)
    {
        $staffDetail = StaffDetail::where('slug', $slug)->firstOrFail();
        $user = User::with(['role', 'staffDetail', 'staffDocuments'])->findOrFail($staffDetail->user_id);
        $roles = Role::where('status', 1)->get();
        $modules = Module::with('permissions')->where('status', 'active')->get();
        $userPermissions = UserPermission::where('user_id', $user->id)->get()->pluck('allowed', 'permission_id')->toArray();

        $staff = $staffDetail;
        return view('staff.edit', compact('staff', 'user', 'roles', 'modules', 'userPermissions'));
    }

    public function update(Request $request, $slug)
    {
        $staffDetail = StaffDetail::where('slug', $slug)->firstOrFail();
        $user = User::findOrFail($staffDetail->user_id);

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'first_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'joining_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            // Handle Slug update if name changes
            $fullName = trim($request->first_name . ' ' . ($request->last_name ?? ''));
            if ($fullName !== $user->name) {
                $newSlug = Str::slug($fullName . '-' . $staffDetail->emp_code);
                $staffDetail->slug = $newSlug;
            }

            // Update User Identity
            $user->update([
                'name' => $fullName,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'status' => $request->status ? 'active' : 'inactive',
            ]);

            // Update Staff Details
            $staffDetail->update(array_merge($request->all(), [
                'full_name' => $fullName,
            ]));

            // User Specific Permission Override
            if ($request->has('user_permissions')) {
                UserPermission::where('user_id', $user->id)->delete();
                foreach ($request->user_permissions as $permissionId => $allowed) {
                    if ($allowed !== "") { // "" means inherit from role
                        UserPermission::create([
                            'user_id' => $user->id,
                            'permission_id' => $permissionId,
                            'allowed' => $allowed == '1',
                        ]);
                    }
                }
            }

            // Handle New Documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    $docName = $request->document_names[$key] ?? $file->getClientOriginalName();
                    $path = $file->store('staff_docs', 'public');

                    StaffDocument::create([
                        'user_id' => $user->id,
                        'document_name' => $docName,
                        'file_path' => $path,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'Staff updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $hasReferrals = \App\Models\User::where('referred_by_staff_id', $id)->exists();

        if (class_exists('\App\Models\StaffMembershipReferral')) {
            $hasReferrals = $hasReferrals || \App\Models\StaffMembershipReferral::where('staff_id', $id)->exists();
        }

        if (class_exists('\App\Models\PurchasedPlan')) {
            $hasReferrals = $hasReferrals || \App\Models\PurchasedPlan::where('referred_by', $id)->exists();
        }

        if ($hasReferrals) {
            return response()->json([
                'error' => 'This staff member cannot be deleted because they have active referrals or commission records associated with them.'
            ]);
        }

        $user->status = 'inactive';
        $user->save();

        if ($user->staffDetail) {
            $user->staffDetail->delete();
        }

        return response()->json(['success' => 'Staff deleted successfully']);
    }

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
            $hasReferrals = \App\Models\User::where('referred_by_staff_id', $id)->exists();

            if (class_exists('\App\Models\StaffMembershipReferral')) {
                $hasReferrals = $hasReferrals || \App\Models\StaffMembershipReferral::where('staff_id', $id)->exists();
            }

            if (class_exists('\App\Models\PurchasedPlan')) {
                $hasReferrals = $hasReferrals || \App\Models\PurchasedPlan::where('referred_by', $id)->exists();
            }

            if ($hasReferrals) {
                $skippedCount++;
            } else {
                $user = User::find($id);
                if ($user) {
                    $user->status = 'inactive';
                    $user->save();

                    if ($user->staffDetail) {
                        $user->staffDetail->delete();
                    }
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
                'message' => "{$totalSelected} selected\n{$deletedCount} deleted\n{$skippedCount} skipped because they have referrals or commissions"
            ]
        ]);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    public function userPermissionsIndex(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with(['role', 'staffDetail'])->whereHas('staffDetail')->get();
            return response()->json(['data' => $users]);
        }
        return view('user-permissions.index');
    }

    public function manageUserPermissions($id)
    {
        $user = User::with('staffDetail')->findOrFail($id);
        $staff = $user->staffDetail;

        // Get the role's allowed permissions
        $rolePermissionIds = RolePermission::where('role_id', $user->role_id)
            ->where('allowed', true)
            ->pluck('permission_id')
            ->toArray();

        // Only show modules/actions that are already granted in that role
        $modules = Module::with([
            'permissions' => function ($query) use ($rolePermissionIds) {
                $query->whereIn('id', $rolePermissionIds);
            }
        ])
            ->where('status', 'active')
            ->whereHas('permissions', function ($query) use ($rolePermissionIds) {
                $query->whereIn('id', $rolePermissionIds);
            })
            ->get();

        $userPermissions = UserPermission::where('user_id', $id)->get()->pluck('allowed', 'permission_id')->toArray();

        return view('user-permissions.manage', compact('user', 'staff', 'modules', 'userPermissions'));
    }

    public function saveUserPermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            DB::beginTransaction();

            // Delete existing overrides (Remove all)
            UserPermission::where('user_id', $user->id)->delete();

            if ($request->has('user_permissions')) {
                // Get all permissions currently allowed by the role (the baseline shown in UI)
                $rolePermissionIds = RolePermission::where('role_id', $user->role_id)
                    ->where('allowed', true)
                    ->pluck('permission_id')
                    ->toArray();

                foreach ($rolePermissionIds as $permId) {
                    // Standard HTML: Unchecked boxes are missing from request
                    $isAllowed = isset($request->user_permissions[$permId]) && $request->user_permissions[$permId] == '1';
                    
                    UserPermission::create([
                        'user_id' => $user->id,
                        'permission_id' => $permId,
                        'allowed' => $isAllowed,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'User permissions updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteDocument($id)
    {
        $doc = StaffDocument::findOrFail($id);
        Storage::disk('public')->delete($doc->file_path);
        $doc->delete();
        return response()->json(['success' => 'Document deleted successfully']);
    }
}
