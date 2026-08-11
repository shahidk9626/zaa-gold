<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceModeService;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingController extends Controller
{
    protected MaintenanceModeService $maintenanceService;

    public function __construct(MaintenanceModeService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Display System Settings page.
     */
    public function index()
    {
        // Enforce Super Admin access only
        if (!$this->maintenanceService->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access system settings.');
        }

        $maintenanceMode = $this->maintenanceService->isEnabled();
        
        return view('admin.system_settings.index', compact('maintenanceMode'));
    }

    /**
     * Toggle Maintenance Mode status.
     */
    public function toggle(Request $request)
    {
        // Enforce Super Admin access only
        if (!$this->maintenanceService->isSuperAdmin()) {
            abort(403, 'Only Super Admin can modify system settings.');
        }

        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $status = (int) $request->status;
        $oldStatus = $this->maintenanceService->isEnabled() ? 'ON' : 'OFF';
        $newStatus = $status ? 'ON' : 'OFF';

        if ($status) {
            $this->maintenanceService->enable();
            
            // Log direct activity for enabling
            $this->logDirectActivity(
                'system_settings',
                0,
                'maintenance_mode_enabled',
                "Maintenance Mode Enabled (changed from {$oldStatus} to {$newStatus})"
            );
        } else {
            $this->maintenanceService->disable();
            
            // Log direct activity for disabling
            $this->logDirectActivity(
                'system_settings',
                0,
                'maintenance_mode_disabled',
                "Maintenance Mode Disabled (changed from {$oldStatus} to {$newStatus})"
            );
        }

        return redirect()->route('admin.system-settings.index')
            ->with('success', 'Maintenance Mode updated successfully.');
    }

    /**
     * Helper to log activity directly.
     */
    protected function logDirectActivity($module, $recordId, $action, $description)
    {
        $userAgent = request()->header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        \App\Models\ActivityLog::create([
            'module_name' => $module,
            'record_id' => $recordId,
            'action_type' => $action,
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
