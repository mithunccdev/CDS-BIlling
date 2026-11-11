@php
    $budgets = \Illuminate\Support\Facades\Schema::hasTable('budgets')
        ? DB::table('budgets as b')
            ->leftJoin('chart_of_accounts as coa', 'b.account_id', '=', 'coa.id')
            ->select('b.*', 'coa.code as account_code', 'coa.name as account_name')
            ->orderByDesc('b.fiscal_year')
            ->orderBy('b.department')
            ->get()
        : collect();

    $totalAllocated = (float) $budgets->sum('allocated_amount');
    $totalSpent = (float) $budgets->sum('spent_amount');
    $totalRemaining = (float) $budgets->sum('remaining_amount');
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Budgeting & Variance Controls</h3>
            <p class="text-muted mb-0">Allocate department budgets against Chart of Accounts and monitor actual spending variance.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="small text-muted text-uppercase fw-bold">Total Allocated Budget</div>
                <div class="fs-3 fw-bolder text-primary mt-1">₹{{ number_format($totalAllocated, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                <div class="small text-muted text-uppercase fw-bold">Total Actual Spent</div>
                <div class="fs-3 fw-bolder text-warning mt-1">₹{{ number_format($totalSpent, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="small text-muted text-uppercase fw-bold">Remaining Budget</div>
                <div class="fs-3 fw-bolder text-success mt-1">₹{{ number_format($totalRemaining, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Budgets Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Department Budget Allocations</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Fiscal Year</th>
                            <th>Department</th>
                            <th>Ledger Account</th>
                            <th>Allocated</th>
                            <th>Spent</th>
                            <th>Remaining</th>
                            <th class="pe-3">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $b)
                            @php
                                $pct = $b->allocated_amount > 0 ? min(100, round(($b->spent_amount / $b->allocated_amount) * 100, 1)) : 0;
                                $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <tr>
                                <td class="ps-3 font-monospace">{{ $b->fiscal_year }}</td>
                                <td class="fw-bold">{{ $b->department }}</td>
                                <td>{{ ($b->account_code ? $b->account_code . ' - ' : '') . ($b->account_name ?? 'Account #' . $b->account_id) }}</td>
                                <td class="fw-bold">₹{{ number_format((float)$b->allocated_amount, 2) }}</td>
                                <td>₹{{ number_format((float)$b->spent_amount, 2) }}</td>
                                <td class="fw-bold {{ $b->remaining_amount >= 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format((float)$b->remaining_amount, 2) }}</td>
                                <td class="pe-3" style="min-width: 140px;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="small text-muted mt-1">{{ $pct }}%</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-pie-chart fs-3 d-block mb-2 opacity-50"></i>
                                    No budgets allocated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
