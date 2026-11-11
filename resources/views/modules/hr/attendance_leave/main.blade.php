@php
    $attendanceLogs = \Illuminate\Support\Facades\Schema::hasTable('attendance_logs')
        ? DB::table('attendance_logs as al')
            ->leftJoin('employees as emp', 'al.employee_id', '=', 'emp.id')
            ->select('al.*', 'emp.employee_code', 'emp.first_name', 'emp.last_name')
            ->orderByDesc('al.date')
            ->orderByDesc('al.id')
            ->limit(25)
            ->get()
        : collect();

    $leaveRequests = \Illuminate\Support\Facades\Schema::hasTable('leave_requests')
        ? DB::table('leave_requests as lr')
            ->leftJoin('employees as emp', 'lr.employee_id', '=', 'emp.id')
            ->select('lr.*', 'emp.employee_code', 'emp.first_name', 'emp.last_name')
            ->orderByDesc('lr.start_date')
            ->orderByDesc('lr.id')
            ->limit(25)
            ->get()
        : collect();
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-calendar-check-fill text-success me-2"></i>Attendance & Leave Management</h3>
            <p class="text-muted mb-0">Daily attendance check-ins, working hours logs, and employee leave requests.</p>
        </div>
    </div>

    <!-- Attendance Register -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Recent Attendance Logs</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Employee</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total Hours</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceLogs as $log)
                            @php
                                $badgeClass = match($log->status) {
                                    'Present' => 'bg-success',
                                    'Half Day', 'Leave' => 'bg-warning text-dark',
                                    default => 'bg-danger',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold">
                                    {{ trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: 'Employee' }}
                                    @if(!empty($log->employee_code))
                                        <span class="text-muted fw-normal">({{ $log->employee_code }})</span>
                                    @endif
                                </td>
                                <td>{{ $log->date }}</td>
                                <td>{{ $log->check_in ?? '-' }}</td>
                                <td>{{ $log->check_out ?? '-' }}</td>
                                <td>{{ number_format((float)$log->total_hours, 2) }} hrs</td>
                                <td class="pe-3 text-end"><span class="badge {{ $badgeClass }}">{{ $log->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No attendance logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leave Requests -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Leave Requests</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Employee</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveRequests as $lr)
                            @php
                                $leaveBadge = match($lr->status) {
                                    'Approved' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold">{{ trim(($lr->first_name ?? '') . ' ' . ($lr->last_name ?? '')) ?: 'Employee' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $lr->leave_type }}</span></td>
                                <td>{{ $lr->start_date }} to {{ $lr->end_date }}</td>
                                <td>{{ $lr->total_days }}</td>
                                <td>{{ $lr->reason ?? '-' }}</td>
                                <td class="pe-3 text-end"><span class="badge {{ $leaveBadge }}">{{ $lr->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No leave requests submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
