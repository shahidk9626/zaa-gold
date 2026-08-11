<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MaintenanceModeService
{
    /**
     * Check if Maintenance Mode is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::get('maintenance_mode', false);
    }

    /**
     * Enable Maintenance Mode.
     */
    public function enable(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => '1', 'description' => 'Global Maintenance Mode Toggle (0 = Off, 1 = On).']
        );
    }

    /**
     * Disable Maintenance Mode.
     */
    public function disable(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => '0', 'description' => 'Global Maintenance Mode Toggle (0 = Off, 1 = On).']
        );
    }

    /**
     * Check if a given user is a Super Admin.
     */
    public function isSuperAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }
        
        return $user->id === 1 || ($user->role && $user->role->slug === 'super-admin');
    }

    /**
     * Determine if the current request should bypass maintenance mode.
     */
    public function shouldBypassMaintenance(): bool
    {
        // If maintenance mode is not enabled, bypass.
        if (!$this->isEnabled()) {
            return true;
        }

        // If the user is authenticated and is a Super Admin, bypass.
        if (Auth::check() && $this->isSuperAdmin()) {
            return true;
        }

        return false;
    }
}
