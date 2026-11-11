@php
    $dbConnected = false;
    $dbLatencyMs = null;
    try {
        $start = microtime(true);
        DB::select('SELECT 1');
        $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
        $dbConnected = true;
    } catch (\Throwable $e) {
        $dbConnected = false;
    }

    $pendingJobs = \Illuminate\Support\Facades\Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
    $failedJobs = \Illuminate\Support\Facades\Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

    $recentAudits = \Illuminate\Support\Facades\Schema::hasTable('audit_logs')
        ? DB::table('audit_logs as al')
            ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
            ->select('al.*', 'u.name as user_name')
            ->orderByDesc('al.created_at')
            ->orderByDesc('al.id')
            ->limit(15)
            ->get()
        : collect();
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-cpu-fill me-2 text-primary"></i>System Health & Queue Telemetry</h4>
            <p class="text-muted small mb-0">Live database connectivity, cache store driver, queue status, and recent security audit events</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 {{ $dbConnected ? 'border-success' : 'border-danger' }}">
                <div class="small text-muted text-uppercase fw-bold">Database</div>
                <div class="fs-5 fw-bold mt-1 {{ $dbConnected ? 'text-success' : 'text-danger' }}">
                    {{ $dbConnected ? 'Healthy (' . $dbLatencyMs . ' ms)' : 'Unavailable' }}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="small text-muted text-uppercase fw-bold">Cache Driver</div>
                <div class="fs-5 fw-bold mt-1 text-primary">{{ strtoupper(config('cache.default')) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <div class="small text-muted text-uppercase fw-bold">Pending Queue Jobs</div>
                <div class="fs-5 fw-bold mt-1">{{ $pendingJobs }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 {{ $failedJobs > 0 ? 'border-danger' : 'border-success' }}">
                <div class="small text-muted text-uppercase fw-bold">Failed Jobs</div>
                <div class="fs-5 fw-bold mt-1 {{ $failedJobs > 0 ? 'text-danger' : 'text-success' }}">{{ $failedJobs }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Recent System Audit Activity</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>Action</th>
                            <th>Module / Table</th>
                            <th>Actor</th>
                            <th class="pe-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAudits as $log)
                            <tr>
                                <td class="ps-4 font-monospace small">{{ $log->created_at }}</td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $log->action }}</span></td>
                                <td class="font-monospace">{{ $log->table_name ?? '-' }}</td>
                                <td>{{ $log->user_name ?? 'System' }}</td>
                                <td class="pe-4 font-monospace small">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No audit activity recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
