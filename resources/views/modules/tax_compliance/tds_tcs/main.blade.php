@php
    $tdsRules = \Illuminate\Support\Facades\Schema::hasTable('statutory_rules')
        ? DB::table('statutory_rules')
            ->where(function ($q) {
                $q->where('rule_key', 'like', '%TDS%')
                  ->orWhere('rule_key', 'like', '%TCS%')
                  ->orWhere('rule_name', 'like', '%TDS%')
                  ->orWhere('rule_name', 'like', '%TCS%');
            })
            ->orderBy('rule_key')
            ->get()
        : collect();

    $hasGl = \Illuminate\Support\Facades\Schema::hasTable('general_ledger') && \Illuminate\Support\Facades\Schema::hasTable('chart_of_accounts');
    $sumAccount = function (string $pattern) use ($hasGl): float {
        if (!$hasGl) {
            return 0.0;
        }
        return (float) DB::table('chart_of_accounts')
            ->where('name', 'like', $pattern)
            ->sum('current_balance');
    };

    $tds194c = $sumAccount('%194C%');
    $tds194j = $sumAccount('%194J%');
    $tcs206c = $sumAccount('%206C%');
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-scissors me-2 text-primary"></i>TDS & TCS Compliance Management</h4>
            <p class="text-muted small mb-0">Tax Deducted at Source (Section 194C, 194J, 194H) & TCS tracking across ledger postings</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-light border-start border-primary border-4 rounded-3">
                <span class="text-muted small">Section 194C (Contractors 1%/2%)</span>
                <h4 class="fw-bold text-primary mt-1">₹{{ number_format($tds194c, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-light border-start border-warning border-4 rounded-3">
                <span class="text-muted small">Section 194J (Professional Services 10%)</span>
                <h4 class="fw-bold text-warning mt-1">₹{{ number_format($tds194j, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-light border-start border-success border-4 rounded-3">
                <span class="text-muted small">TCS Section 206C(1H) (0.1%)</span>
                <h4 class="fw-bold text-success mt-1">₹{{ number_format($tcs206c, 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Configured Statutory TDS / TCS Rules</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Rule Key</th>
                            <th>Rule Name</th>
                            <th>Rate (%)</th>
                            <th>Threshold</th>
                            <th class="pe-4">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tdsRules as $rule)
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-primary">{{ $rule->rule_key }}</td>
                                <td>{{ $rule->rule_name }}</td>
                                <td>{{ $rule->percentage !== null ? $rule->percentage . '%' : '-' }}</td>
                                <td>{{ $rule->salary_threshold !== null ? '₹' . number_format((float)$rule->salary_threshold, 2) : '-' }}</td>
                                <td class="pe-4 text-muted small">{{ $rule->description ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No TDS/TCS statutory rules configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
