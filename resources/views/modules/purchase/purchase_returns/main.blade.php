@php
    $returns = \Illuminate\Support\Facades\Schema::hasTable('credit_debit_notes')
        ? DB::table('credit_debit_notes as cdn')
            ->leftJoin('vendors as v', 'cdn.party_id', '=', 'v.id')
            ->where('cdn.party_type', 'Vendor')
            ->where('cdn.note_type', 'Debit Note')
            ->select('cdn.*', 'v.name as vendor_name')
            ->orderByDesc('cdn.id')
            ->get()
        : collect();
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-arrow-counterclockwise me-2 text-primary"></i>Purchase Returns & Vendor Debit Notes</h4>
            <p class="text-muted small mb-0">Track rejected goods returns to suppliers and vendor debit note adjustments</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Return / Note No</th>
                            <th>Vendor Name</th>
                            <th>Reference Bill</th>
                            <th>Reason / Note</th>
                            <th class="text-end">Total Debit Value (₹)</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            @php
                                $badgeClass = match($ret->status) {
                                    'Issued', 'Approved', 'Posted' => 'bg-success-subtle text-success',
                                    'Cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-secondary-subtle text-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-primary">{{ $ret->note_no }}</td>
                                <td class="fw-semibold">{{ $ret->vendor_name ?? ('Vendor #' . $ret->party_id) }}</td>
                                <td class="font-monospace">{{ $ret->reference_invoice_no ?? '-' }}</td>
                                <td>{{ $ret->reason ?? '-' }}</td>
                                <td class="text-end font-monospace fw-bold text-danger">₹{{ number_format((float)$ret->total_amount, 2) }}</td>
                                <td class="text-center pe-4"><span class="badge {{ $badgeClass }} rounded-pill px-3 py-1">{{ $ret->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-arrow-counterclockwise fs-3 d-block mb-2 opacity-50"></i>
                                    No purchase returns or vendor debit notes recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
