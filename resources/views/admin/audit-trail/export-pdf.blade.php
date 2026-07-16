<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Trail Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f2f2f2; text-align: left; }
    </style>
</head>
<body>
    <h2>Audit Trail Export</h2>
    <p>Generated at {{ now()->format('Y-m-d H:i:s') }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Module</th>
                <th>Action</th>
                <th>User</th>
                <th>Record ID</th>
                <th>Description</th>
                <th>IP</th>
                <th>Browser</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($log->module_name) }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($log->action_type) }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ $log->record_id }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ $log->browser }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
