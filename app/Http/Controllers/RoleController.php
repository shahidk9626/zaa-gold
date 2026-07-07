<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::latest()->get();
            return response()->json(['data' => $roles]);
        }
        $modules = Module::with('permissions')->where('status', 'active')->get();
        return view('roles.index', compact('modules'));
    }

    public function store(Request $request)
    {
        $slug = Str::slug($request->name);
        $existingSoftDeleted = Role::onlyTrashed()
            ->where(function($query) use ($request, $slug) {
                $query->where('name', $request->name)
                      ->orWhere('slug', $slug);
            })
            ->first();
        if ($existingSoftDeleted) {
            RolePermission::where('role_id', $existingSoftDeleted->id)->delete();
            $existingSoftDeleted->forceDelete();
        }

        $request->validate([
            'name' => 'required|unique:roles,name',
            'status' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            if ($request->has('permissions')) {
                foreach ($request->permissions as $permissionId => $allowed) {
                    RolePermission::create([
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'allowed' => $allowed == '1',
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'Role created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $slug = Str::slug($request->name);
        $existingSoftDeleted = Role::onlyTrashed()
            ->where(function($query) use ($request, $slug) {
                $query->where('name', $request->name)
                      ->orWhere('slug', $slug);
            })
            ->first();
        if ($existingSoftDeleted && $existingSoftDeleted->id !== $role->id) {
            RolePermission::where('role_id', $existingSoftDeleted->id)->delete();
            $existingSoftDeleted->forceDelete();
        }

        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'status' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'status' => $request->status,
            ]);

            if ($request->has('permissions')) {
                RolePermission::where('role_id', $role->id)->delete();
                foreach ($request->permissions as $permissionId => $allowed) {
                    RolePermission::create([
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'allowed' => $allowed == '1',
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'Role updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPermissions($id)
    {
        $permissions = RolePermission::where('role_id', $id)->get();
        return response()->json($permissions);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        $hasStaff = \App\Models\User::where('role_id', $id)
            ->whereHas('staffDetail')
            ->exists();

        if ($hasStaff) {
            return response()->json([
                'error' => 'This role cannot be deleted because it is currently assigned to one or more staff members. Please remove or reassign staff before deleting this role.'
            ]);
        }

        $role->delete();

        return response()->json(['success' => 'Role deleted successfully.']);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:roles,id',
        ]);

        $ids = $request->ids;
        $totalSelected = count($ids);
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($ids as $id) {
            $hasStaff = \App\Models\User::where('role_id', $id)
                ->whereHas('staffDetail')
                ->exists();

            if ($hasStaff) {
                $skippedCount++;
            } else {
                $role = Role::find($id);
                if ($role) {
                    $role->delete();
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
                'message' => "{$totalSelected} selected\n{$deletedCount} deleted\n{$skippedCount} skipped because staff are assigned"
            ]
        ]);
    }

    public function rolePermissionsIndex(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::latest()->get();
            return response()->json(['data' => $roles]);
        }
        return view('role-permissions.index');
    }

    public function manageRolePermissions($id)
    {
        $role = Role::findOrFail($id);
        $modules = Module::with('permissions')->where('status', 'active')->get();
        $rolePermissions = RolePermission::where('role_id', $id)->get()->pluck('allowed', 'permission_id')->toArray();

        return view('role-permissions.manage', compact('role', 'modules', 'rolePermissions'));
    }

    public function toggleStatus($id)
    {
        $role = Role::findOrFail($id);
        $role->update(['status' => !$role->status]);

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
