@php
    $claims = \Illuminate\Support\Facades\Schema::hasTable('expense_claims')
        ? DB::table('expense_claims as ec')
            ->leftJoin('employees as emp', 'ec.employee_id', '=', 'emp.id')
            ->select('ec.*', 'emp.first_name', 'emp.last_name', 'emp.employee_code')
            ->orderByDesc('ec.claim_date')
            ->orderByDesc('ec.id')
            ->get()
        : collect();
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-info me-2"></i>Expense Claims & Reimbursements</h3>
            <p class="text-muted mb-0">Submit employee expense claims, track approval status, and review disbursements.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Claim No</th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $claim)
                            @php
                                $badgeClass = match($claim->status) {
                                    'Paid', 'Approved' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-primary font-monospace">{{ $claim->claim_no }}</td>
                                <td>{{ trim(($claim->first_name ?? '') . ' ' . ($claim->last_name ?? '')) ?: 'Unassigned' }}</td>
                                <td>{{ $claim->claim_date }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $claim->category }}</span></td>
                                <td class="fw-bold">₹{{ number_format((float)$claim->amount, 2) }}</td>
                                <td>{{ $claim->description ?? '-' }}</td>
                                <td class="pe-3 text-end"><span class="badge {{ $badgeClass }}">{{ $claim->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt-cutoff fs-3 d-block mb-2 opacity-50"></i>
                                    No expense claims submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
