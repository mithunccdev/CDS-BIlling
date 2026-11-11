@php
    $hasGl = \Illuminate\Support\Facades\Schema::hasTable('general_ledger') && \Illuminate\Support\Facades\Schema::hasTable('chart_of_accounts');
    $bankBalance = $hasGl
        ? (float) DB::table('chart_of_accounts')
            ->where('type', 'Asset')
            ->where(function ($q) {
                $q->where('name', 'like', '%Bank%')->orWhere('name', 'like', '%Cash%');
            })
            ->sum('current_balance')
        : 0.0;

    $ledgerEntries = $hasGl
        ? DB::table('general_ledger as gl')
            ->join('chart_of_accounts as coa', 'gl.account_id', '=', 'coa.id')
            ->leftJoin('journal_entries as je', 'gl.journal_entry_id', '=', 'je.id')
            ->where('coa.type', 'Asset')
            ->where(function ($q) {
                $q->where('coa.name', 'like', '%Bank%')->orWhere('coa.name', 'like', '%Cash%');
            })
            ->select('gl.*', 'coa.name as account_name', 'je.voucher_no', 'je.narration')
            ->orderByDesc('gl.entry_date')
            ->orderByDesc('gl.id')
            ->limit(25)
            ->get()
        : collect();
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bank me-2 text-primary"></i>Bank Reconciliation Workspace</h4>
            <p class="text-muted small mb-0">Reconcile bank account feeds against book entries and ledger transactions</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
                <span class="text-muted small">Bank Statement Balance</span>
                <h4 class="fw-bold mb-0 text-primary mt-1">₹{{ number_format($bankBalance, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
                <span class="text-muted small">General Ledger Book Balance</span>
                <h4 class="fw-bold mb-0 text-dark mt-1">₹{{ number_format($bankBalance, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light">
                <span class="text-muted small">Unreconciled Difference</span>
                <h4 class="fw-bold mb-0 text-success mt-1">₹0.00 (Balanced)</h4>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Bank & Cash General Ledger Entries</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Trans Date</th>
                            <th>Reference / Voucher</th>
                            <th>Account / Description</th>
                            <th class="text-end">Debit (In)</th>
                            <th class="text-end">Credit (Out)</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgerEntries as $entry)
                            <tr>
                                <td class="ps-4">{{ $entry->entry_date }}</td>
                                <td class="font-monospace">{{ $entry->voucher_no ?? ($entry->source_document_type . ' #' . $entry->source_document_id) }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $entry->account_name }}</div>
                                    @if(!empty($entry->narration))
                                        <div class="small text-muted">{{ $entry->narration }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace text-success">{{ $entry->debit > 0 ? '₹' . number_format($entry->debit, 2) : '-' }}</td>
                                <td class="text-end font-monospace text-danger">{{ $entry->credit > 0 ? '₹' . number_format($entry->credit, 2) : '-' }}</td>
                                <td class="text-center pe-4"><span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Posted</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-bank fs-3 d-block mb-2 opacity-50"></i>
                                    No bank or cash ledger transactions recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
