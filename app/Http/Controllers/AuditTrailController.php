<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AuditTrailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditTrailController extends Controller
{
    public function __construct(protected AuditTrailService $auditTrailService)
    {
    }

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request)->with('user');

        $auditLogs = $query->latest()->paginate(20)->withQueryString();
        $modules = ActivityLog::query()->select('module_name')->distinct()->orderBy('module_name')->pluck('module_name');
        $actions = ActivityLog::query()->select('action_type')->distinct()->orderBy('action_type')->pluck('action_type');
        $users = User::query()->with('role')->orderBy('name')->get(['id', 'name', 'email', 'role_id']);

        return view('admin.audit-trail.index', compact('auditLogs', 'modules', 'actions', 'users'));
    }

    public function show(ActivityLog $auditTrail)
    {
        $auditTrail->load('user');
        $timeline = $this->auditTrailService->generateTimeline($auditTrail);

        return view('admin.audit-trail.show', [
            'auditLog' => $auditTrail,
            'timeline' => $timeline,
        ]);
    }

    public function export(Request $request, string $type)
    {
        $logs = $this->filteredQuery($request)->with('user')->latest()->get();
        $fileName = 'Audit_Trail_' . now()->format('YmdHis');

        app(AuditTrailService::class)->captureEvent(
            'audit_trail',
            0,
            'export',
            null,
            ['type' => $type, 'filters' => $request->query()],
            'Audit trail exported as ' . strtoupper($type)
        );

        if ($type === 'pdf') {
            return Pdf::loadView('admin.audit-trail.export-pdf', ['logs' => $logs])
                ->download($fileName . '.pdf');
        }

        $extension = $type === 'excel' ? 'xls' : 'csv';
        $contentType = $type === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';

        return new StreamedResponse(function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Module', 'Action', 'User', 'Record ID', 'Description', 'IP', 'Browser', 'Device', 'Platform']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    Str::headline($log->module_name),
                    Str::headline($log->action_type),
                    $log->user->name ?? 'System',
                    $log->record_id,
                    $log->description,
                    $log->ip_address,
                    $log->browser,
                    $log->device,
                    $log->platform,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.' . $extension . '"',
        ]);
    }

    protected function filteredQuery(Request $request)
    {
        return ActivityLog::query()
            ->when($request->filled('module'), fn ($query) => $query->where('module_name', $request->module))
            ->when($request->filled('action'), fn ($query) => $query->where('action_type', $request->action))
            ->when($request->filled('user_id'), fn ($query) => $query->where('created_by_id', $request->user_id))
            ->when($request->filled('record_id'), fn ($query) => $query->where('record_id', $request->record_id))
            ->when($request->filled('ip_address'), fn ($query) => $query->where('ip_address', 'like', '%' . $request->ip_address . '%'))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->to_date))
            ->when($request->filled('customer_id'), fn ($query) => $query->where(function ($inner) use ($request) {
                $inner->where('created_by_id', $request->customer_id)
                    ->orWhereJsonContains('old_data->customer_id', (int) $request->customer_id)
                    ->orWhereJsonContains('new_data->customer_id', (int) $request->customer_id);
            }))
            ->when($request->filled('staff_id'), fn ($query) => $query->where(function ($inner) use ($request) {
                $inner->where('created_by_id', $request->staff_id)
                    ->orWhereJsonContains('old_data->staff_id', (int) $request->staff_id)
                    ->orWhereJsonContains('new_data->staff_id', (int) $request->staff_id)
                    ->orWhereJsonContains('old_data->assigned_staff_id', (int) $request->staff_id)
                    ->orWhereJsonContains('new_data->assigned_staff_id', (int) $request->staff_id);
            }))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->search . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('module_name', 'like', $term)
                        ->orWhere('record_id', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term));
                });
            });
    }
}
