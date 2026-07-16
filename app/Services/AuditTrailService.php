<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrailService
{
    protected array $ignoredFields = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'email_verified_at',
        'password',
        'updated_by_id',
    ];

    public function captureEvent(
        string $module,
        int|string|null $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?Request $request = null,
        ?int $performedBy = null,
        ?string $performedByType = null
    ): ActivityLog {
        $request ??= request();
        $diff = $this->generateFieldDifferences($oldValues ?? [], $newValues ?? []);
        $userAgent = (string) $request->userAgent();
        $actorId = $performedBy ?? Auth::id();

        return ActivityLog::create([
            'module_name' => $module,
            'record_id' => $recordId ?: 0,
            'action_type' => $action,
            'old_data' => $diff['old_values'] ?: $oldValues,
            'new_data' => $diff['new_values'] ?: $newValues,
            'changed_fields' => $diff['changed_fields'],
            'description' => $description ?: $this->description($module, $recordId, $action),
            'created_by_id' => $actorId,
            'performed_by_type' => $performedByType ?: ($actorId ? 'user' : 'system'),
            'ip_address' => $request->ip(),
            'browser' => $this->parseBrowser($userAgent),
            'device' => $this->parseDevice($userAgent),
            'platform' => $this->parsePlatform($userAgent),
            'user_agent' => $userAgent,
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'request_id' => $request->headers->get('X-Request-Id') ?: (string) Str::uuid(),
        ]);
    }

    public function captureModelEvent(Model $model, string $action, ?array $oldValues = null, ?array $newValues = null): ?ActivityLog
    {
        $module = $this->moduleName($model);
        $oldValues = $this->sanitizeValues($oldValues ?? []);
        $newValues = $this->sanitizeValues($newValues ?? []);
        $diff = $this->generateFieldDifferences($oldValues, $newValues);

        if (in_array($action, ['update', 'status_change'], true) && empty($diff['changed_fields'])) {
            return null;
        }

        if ($action === 'update' && $this->isStatusChange($diff['changed_fields'])) {
            $action = 'status_change';
        }

        return $this->captureEvent(
            $module,
            $model->getKey(),
            $action,
            in_array($action, ['create', 'restore'], true) ? null : ($diff['old_values'] ?: $oldValues),
            in_array($action, ['delete', 'force_delete'], true) ? null : ($diff['new_values'] ?: $newValues),
            $this->description($module, $model->getKey(), $action)
        );
    }

    public function compareModels(Model $model): array
    {
        $dirtyKeys = array_keys($model->getDirty());

        return $this->generateFieldDifferences(
            $this->sanitizeValues(Arr::only($model->getOriginal(), $dirtyKeys)),
            $this->sanitizeValues(Arr::only($model->getAttributes(), $dirtyKeys))
        );
    }

    public function generateFieldDifferences(array $oldValues, array $newValues): array
    {
        $old = [];
        $new = [];
        $fields = [];
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($keys as $field) {
            if (in_array($field, $this->ignoredFields, true)) {
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if ($this->normalizeForCompare($oldValue) === $this->normalizeForCompare($newValue)) {
                continue;
            }

            $old[$field] = $oldValue;
            $new[$field] = $newValue;
            $fields[] = $field;
        }

        return [
            'old_values' => $old,
            'new_values' => $new,
            'changed_fields' => $fields,
        ];
    }

    public function generateTimeline(ActivityLog $log)
    {
        return ActivityLog::query()
            ->with('user')
            ->where('module_name', $log->module_name)
            ->where('record_id', $log->record_id)
            ->orderBy('created_at')
            ->get();
    }

    public function parseBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/'), str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') => 'Safari',
            str_contains($userAgent, 'MSIE'), str_contains($userAgent, 'Trident') => 'Internet Explorer',
            default => 'Unknown',
        };
    }

    public function parseDevice(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($userAgent, 'iPad'), str_contains($userAgent, 'Tablet') => 'Tablet',
            str_contains($userAgent, 'Mobile'), str_contains($userAgent, 'Android'), str_contains($userAgent, 'iPhone') => 'Mobile',
            default => 'Desktop',
        };
    }

    public function parsePlatform(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X'), str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }

    protected function moduleName(Model $model): string
    {
        if (property_exists($model, 'activityModule')) {
            return $model->activityModule;
        }

        return Str::snake(class_basename($model));
    }

    protected function sanitizeValues(array $values): array
    {
        return Arr::except($values, $this->ignoredFields);
    }

    protected function normalizeForCompare(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    protected function isStatusChange(array $fields): bool
    {
        return !empty($fields) && collect($fields)->contains(fn ($field) => str_contains($field, 'status'));
    }

    protected function description(string $module, int|string|null $recordId, string $action): string
    {
        $label = Str::headline($action);

        return "{$label} in " . Str::headline($module) . ($recordId ? " (ID: {$recordId})" : '');
    }
}
