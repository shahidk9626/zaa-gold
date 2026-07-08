<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasCreatorUpdater
{
    protected static function bootHasCreatorUpdater()
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by_id) {
                $model->created_by_id = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by_id = Auth::id();
            }
        });
    }
}
