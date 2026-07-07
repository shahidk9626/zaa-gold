<?php

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Auth;

if (!function_exists('hasPermission')) {
    /**
     * Check if a user has a specific permission.
     *
     * @param string $slug
     * @param \App\Models\User|null $user
     * @return bool
     */
    function hasPermission($slug, $user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        // 1. Super Admin Rule (User ID = 1)
        if ($user->id === 1) {
            return true;
        }

        $permission = Permission::where('slug', $slug)->first();

        if (!$permission) {
            return false;
        }

        // 2. User Specific Override
        $userOverride = UserPermission::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        if ($userOverride !== null) {
            return (bool) $userOverride->allowed;
        }

        // 3. Role Permission
        if (!$user->role_id) {
            return false;
        }

        $rolePermission = RolePermission::where('role_id', $user->role_id)
            ->where('permission_id', $permission->id)
            ->first();

        if ($rolePermission !== null) {
            return (bool) $rolePermission->allowed;
        }

        return false;
    }
}
