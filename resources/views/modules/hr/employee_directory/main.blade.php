@php
    $employees = \Illuminate\Support\Facades\Schema::hasTable('employees')
        ? DB::table('employees')->orderBy('employee_code')->get()
        : collect();

    $totalEmployees = $employees->count();
    $activeCount = $employees->where('status', 'Active')->count();
    $onLeaveCount = $employees->where('status', 'On Leave')->count();
    $totalBasicSalary = (float) $employees->sum('basic_salary');
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Employee Directory</h3>
            <p class="text-muted mb-0">Centralized organization directory of employees, departments, designations, and compensation.</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="small text-muted text-uppercase fw-bold">Total Staff</div>
                <div class="fs-3 fw-bolder mt-1">{{ $totalEmployees }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="small text-muted text-uppercase fw-bold">Active Employees</div>
                <div class="fs-3 fw-bolder text-success mt-1">{{ $activeCount }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                <div class="small text-muted text-uppercase fw-bold">On Leave</div>
                <div class="fs-3 fw-bolder text-warning mt-1">{{ $onLeaveCount }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <div class="small text-muted text-uppercase fw-bold">Monthly Basic Payroll</div>
                <div class="fs-3 fw-bolder mt-1">₹{{ number_format($totalBasicSalary, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Code</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Joining Date</th>
                            <th>Basic Salary</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $statusBadge = match($emp->status) {
                                    'Active' => 'bg-success',
                                    'On Leave' => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-primary font-monospace">{{ $emp->employee_code }}</td>
                                <td>
                                    <div class="fw-bold">{{ trim($emp->first_name . ' ' . ($emp->last_name ?? '')) }}</div>
                                    <div class="small text-muted">{{ $emp->email }}</div>
                                </td>
                                <td>{{ $emp->department }}</td>
                                <td>{{ $emp->designation }}</td>
                                <td>{{ $emp->joining_date ?? '-' }}</td>
                                <td class="fw-bold">₹{{ number_format((float)$emp->basic_salary, 2) }}</td>
                                <td class="pe-3 text-end"><span class="badge {{ $statusBadge }}">{{ $emp->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-3 d-block mb-2 opacity-50"></i>
                                    No employee records added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
