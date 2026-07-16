<?php

namespace App\Traits;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\SoftDeletes;

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

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function ($model) {
                $model->logActivity('restore', null, $model->getAttributes());
            });

            static::forceDeleted(function ($model) {
                $model->logActivity('force_delete', $model->getAttributes(), null);
            });
        }
    }

    protected function logActivity($action, $old = null, $new = null)
    {
        app(AuditTrailService::class)->captureModelEvent($this, $action, $old, $new);
    }
}
