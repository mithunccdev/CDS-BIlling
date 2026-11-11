@php
    $hasContracts = \Illuminate\Support\Facades\Schema::hasTable('purchase_contracts');
    $hasEvals = \Illuminate\Support\Facades\Schema::hasTable('vendor_evaluations');
    $contracts = $hasContracts
        ? DB::table('purchase_contracts as pc')
            ->leftJoin('vendors as v', 'pc.vendor_id', '=', 'v.id')
            ->select('pc.*', 'v.name as vendor_name')
            ->orderByDesc('pc.id')
            ->get()
        : collect();

    $vendorRatings = $hasEvals
        ? DB::table('vendor_evaluations')
            ->select('vendor_id', DB::raw('AVG(overall_rating) as avg_rating'))
            ->groupBy('vendor_id')
            ->pluck('avg_rating', 'vendor_id')
        : collect();
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-text-fill text-purple me-2"></i>Vendor Contracts & Performance Evaluation</h3>
            <p class="text-muted mb-0">Manage long-term supplier blanket contracts, expiry timelines, and supplier performance scoring.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Purchase Contracts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Contract No</th>
                            <th>Vendor Name</th>
                            <th>Validity Period</th>
                            <th>Contract Value</th>
                            <th>Remaining Amount</th>
                            <th>Vendor Score</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            @php
                                $rating = $vendorRatings[$contract->vendor_id] ?? null;
                                $statusBadge = match($contract->status) {
                                    'Active' => 'bg-success',
                                    'Expired', 'Terminated' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-primary font-monospace">{{ $contract->contract_no }}</td>
                                <td class="fw-bold">{{ $contract->vendor_name ?? 'Vendor #' . $contract->vendor_id }}</td>
                                <td>{{ $contract->start_date }} to {{ $contract->end_date }}</td>
                                <td>₹{{ number_format((float)$contract->max_amount, 2) }}</td>
                                <td class="text-success fw-bold">₹{{ number_format((float)$contract->remaining_amount, 2) }}</td>
                                <td>
                                    @if($rating !== null)
                                        <span class="badge bg-success"><i class="bi bi-star-fill me-1"></i> {{ number_format((float)$rating, 1) }} / 10</span>
                                    @else
                                        <span class="text-muted small">Not rated</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end"><span class="badge {{ $statusBadge }}">{{ $contract->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-text fs-3 d-block mb-2 opacity-50"></i>
                                    No vendor contracts recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
