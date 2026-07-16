<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected static array $columnCache = [];

    protected $fillable = [
        'module',
        'module_name',
        'action',
        'record_id',
        'action_type',
        'old_values',
        'old_data',
        'new_values',
        'new_data',
        'changed_fields',
        'description',
        'performed_by',
        'created_by_id',
        'performed_by_type',
        'ip_address',
        'browser',
        'device',
        'platform',
        'user_agent',
        'url',
        'http_method',
        'request_id',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changed_fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $log) {
            $log->module_name ??= $log->getAttribute('module');
            $log->action_type ??= $log->getAttribute('action');
            $log->old_data ??= $log->getAttribute('old_values');
            $log->new_data ??= $log->getAttribute('new_values');
            $log->created_by_id ??= $log->getAttribute('performed_by');

            if ($log->hasColumn('performed_by_type')) {
                $log->performed_by_type ??= $log->created_by_id ? 'user' : 'system';
            }

            if ($log->hasColumn('changed_fields')) {
                $log->changed_fields ??= $log->buildChangedFields();
            }

            if ($log->hasColumn('request_id')) {
                $log->request_id ??= request()?->headers->get('X-Request-Id') ?: (string) Str::uuid();
            }

            if ($log->hasColumn('url')) {
                $log->url ??= request()?->fullUrl();
            }

            if ($log->hasColumn('http_method')) {
                $log->http_method ??= request()?->method();
            }

            $log->pruneMissingColumns();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function setModuleAttribute($value): void
    {
        $this->attributes['module_name'] = $value;
    }

    public function getModuleAttribute(): ?string
    {
        return $this->module_name;
    }

    public function setActionAttribute($value): void
    {
        $this->attributes['action_type'] = $value;
    }

    public function getActionAttribute(): ?string
    {
        return $this->action_type;
    }

    public function setOldValuesAttribute($value): void
    {
        $this->attributes['old_data'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getOldValuesAttribute(): ?array
    {
        return $this->old_data;
    }

    public function setNewValuesAttribute($value): void
    {
        $this->attributes['new_data'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getNewValuesAttribute(): ?array
    {
        return $this->new_data;
    }

    public function setPerformedByAttribute($value): void
    {
        $this->attributes['created_by_id'] = $value;
    }

    public function getPerformedByAttribute(): ?int
    {
        return $this->created_by_id;
    }

    protected function buildChangedFields(): array
    {
        $old = $this->old_data ?: [];
        $new = $this->new_data ?: [];

        return array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
    }

    protected function hasColumn(string $column): bool
    {
        return self::$columnCache[$column] ??= Schema::hasColumn($this->getTable(), $column);
    }

    protected function pruneMissingColumns(): void
    {
        foreach (array_keys($this->attributes) as $column) {
            if (!$this->hasColumn($column)) {
                unset($this->attributes[$column]);
            }
        }
    }
}
