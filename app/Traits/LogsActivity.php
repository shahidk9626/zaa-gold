<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('create', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = [];
            $new = [];
            foreach ($model->getDirty() as $key => $value) {
                if (in_array($key, ['updated_at', 'created_at', 'updated_by_id'])) {
                    continue;
                }
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            if (!empty($new)) {
                $model->logActivity('update', $old, $new);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('delete', $model->getAttributes(), null);
        });
    }

    protected function logActivity($action, $old = null, $new = null)
    {
        $moduleName = strtolower(class_basename($this));
        if (property_exists($this, 'activityModule')) {
            $moduleName = $this->activityModule;
        }

        $description = ucfirst($action) . "d record in {$moduleName} (ID: {$this->id})";

        $userAgent = Request::header('User-Agent');
        $browser = $this->parseBrowser($userAgent);

        ActivityLog::create([
            'module_name' => $moduleName,
            'record_id' => $this->id,
            'action_type' => $action,
            'old_data' => $old,
            'new_data' => $new,
            'description' => $description,
            'created_by_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }

    protected function parseBrowser($userAgent)
    {
        if (empty($userAgent)) return 'Unknown';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) return 'Opera';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }
}
