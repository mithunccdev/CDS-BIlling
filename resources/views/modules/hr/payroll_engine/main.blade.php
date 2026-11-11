@php
    $hasPayroll = \Illuminate\Support\Facades\Schema::hasTable('payroll_runs') && \Illuminate\Support\Facades\Schema::hasTable('payslips');
    $latestRun = $hasPayroll ? DB::table('payroll_runs')->orderByDesc('id')->first() : null;
    $payslips = ($hasPayroll && $latestRun)
        ? DB::table('payslips as ps')
            ->leftJoin('employees as emp', 'ps.employee_id', '=', 'emp.id')
            ->where('ps.payroll_run_id', $latestRun->id)
            ->select('ps.*', 'emp.first_name', 'emp.last_name', 'emp.employee_code')
            ->orderBy('emp.employee_code')
            ->get()
        : collect();

    $grossTotal = (float) ($latestRun->gross_salary ?? 0);
    $pfTotal = (float) ($latestRun->pf_deductions ?? 0);
    $esiPtTotal = (float) (($latestRun->esi_deductions ?? 0) + ($latestRun->pt_deductions ?? 0));
    $netTotal = (float) ($latestRun->net_salary ?? 0);
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-cash-stack text-warning me-2"></i>Payroll & Statutory Deductions Engine</h3>
            <p class="text-muted mb-0">Process monthly salaries, Provident Fund (PF 12%), ESI (0.75%), Professional Tax (PT), and payslips.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="small text-muted text-uppercase fw-bold">Gross Salary</div>
                <div class="fs-3 fw-bolder text-primary mt-1">₹{{ number_format($grossTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-danger">
                <div class="small text-muted text-uppercase fw-bold">PF Deductions (12%)</div>
                <div class="fs-3 fw-bolder text-danger mt-1">₹{{ number_format($pfTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <div class="small text-muted text-uppercase fw-bold">ESI & PT Deductions</div>
                <div class="fs-3 fw-bolder text-info mt-1">₹{{ number_format($esiPtTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="small text-muted text-uppercase fw-bold">Net Salary Payable</div>
                <div class="fs-3 fw-bolder text-success mt-1">₹{{ number_format($netTotal, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Payroll Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">{{ $latestRun ? ($latestRun->payroll_period . ' Payroll Register') : 'Payroll Register' }}</h5>
            <span class="badge {{ $latestRun ? 'bg-success' : 'bg-secondary' }} px-3 py-2 fs-6">
                Status: {{ $latestRun->status ?? 'No Run Yet' }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Employee</th>
                            <th>Basic Pay</th>
                            <th>HRA</th>
                            <th>Allowances</th>
                            <th>PF (12%)</th>
                            <th>ESI / PT</th>
                            <th>Net Salary</th>
                            <th class="text-end pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $slip)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <div>{{ trim(($slip->first_name ?? '') . ' ' . ($slip->last_name ?? '')) ?: 'Employee' }}</div>
                                    <div class="small text-muted">{{ $slip->employee_code ?? '-' }}</div>
                                </td>
                                <td>₹{{ number_format((float)$slip->basic_salary, 2) }}</td>
                                <td>₹{{ number_format((float)$slip->hra, 2) }}</td>
                                <td>₹{{ number_format((float)$slip->allowances, 2) }}</td>
                                <td class="text-danger">-₹{{ number_format((float)$slip->pf, 2) }}</td>
                                <td class="text-danger">-₹{{ number_format((float)($slip->esi + $slip->pt), 2) }}</td>
                                <td class="fw-bold text-success">₹{{ number_format((float)$slip->net_pay, 2) }}</td>
                                <td class="text-end pe-3"><span class="badge bg-secondary-subtle text-dark">{{ $slip->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-cash-stack fs-3 d-block mb-2 opacity-50"></i>
                                    No payroll runs processed yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
