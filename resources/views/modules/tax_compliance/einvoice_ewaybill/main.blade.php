@php
    $company = \Illuminate\Support\Facades\Schema::hasTable('companies')
        ? DB::table('companies')->first()
        : null;

    $latestEinvoice = \Illuminate\Support\Facades\Schema::hasTable('einvoice_logs')
        ? DB::table('einvoice_logs as el')
            ->leftJoin('sales_invoices as si', 'el.sales_invoice_id', '=', 'si.id')
            ->leftJoin('customers as c', 'si.customer_id', '=', 'c.id')
            ->select('el.*', 'si.invoice_no', 'si.invoice_date', 'si.total_amount', 'c.name as buyer_name', 'c.gstin as buyer_gstin', 'c.state as buyer_state')
            ->orderByDesc('el.id')
            ->first()
        : null;

    $ewaybills = \Illuminate\Support\Facades\Schema::hasTable('ewaybill_logs')
        ? DB::table('ewaybill_logs as ewb')
            ->leftJoin('delivery_notes as dn', 'ewb.delivery_note_id', '=', 'dn.id')
            ->select('ewb.*', 'dn.delivery_no')
            ->orderByDesc('ewb.id')
            ->get()
        : collect();

    $payloadPreview = $latestEinvoice
        ? json_encode([
            'Version' => '1.1',
            'TranDtls' => ['TaxSch' => 'GST', 'SupTyp' => 'B2B'],
            'DocDtls' => ['Typ' => 'INV', 'No' => $latestEinvoice->invoice_no, 'Dt' => $latestEinvoice->invoice_date],
            'SellerDtls' => ['LglNm' => $company?->name ?? 'Not Configured', 'Gstin' => $company?->gstin ?? 'Not Set'],
            'BuyerDtls' => ['LglNm' => $latestEinvoice->buyer_name ?? 'N/A', 'Gstin' => $latestEinvoice->buyer_gstin ?? 'URP'],
            'ValDtls' => ['TotInvVal' => (float) $latestEinvoice->total_amount],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        : null;
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>E-Invoicing (IRN) & E-Way Bill Compliance</h4>
            <p class="text-muted small mb-0">NIC E-Invoice IRN payload preview & E-Way Bill generation registry</p>
        </div>
    </div>

    <!-- Live IRN Preview Box -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-success"></i>Latest E-Invoice IRN Payload</h6>
            @if($latestEinvoice)
                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Status: {{ $latestEinvoice->status }}</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">No IRN Generated</span>
            @endif
        </div>
        @if($latestEinvoice)
            <div class="row g-3">
                <div class="col-md-8">
                    <pre class="bg-dark text-success font-monospace p-3 rounded small mb-0" style="max-height: 220px; overflow-y: auto;">{{ $payloadPreview }}</pre>
                </div>
                <div class="col-md-4 text-center">
                    <div class="border rounded bg-white p-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                        <i class="bi bi-qr-code fs-1 text-primary mb-2"></i>
                        <span class="d-block text-muted small">Ack No: {{ $latestEinvoice->ack_no ?? 'Pending' }}</span>
                        <span class="badge bg-primary-subtle text-primary font-monospace mt-2 text-break">IRN: {{ $latestEinvoice->irn ?? 'Pending' }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="text-muted small py-3 text-center">
                No E-Invoice IRN records generated yet. Issue a GST Sales Invoice and register its IRN log to preview the JSON payload here.
            </div>
        @endif
    </div>

    <!-- E-Way Bill Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">E-Way Bills Generated Log (Threshold &gt; ₹50,000)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">E-Way Bill No</th>
                            <th>Delivery Note</th>
                            <th>Generated Date</th>
                            <th>Valid Until</th>
                            <th>Distance (KM)</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ewaybills as $ewb)
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-primary">{{ $ewb->ewaybill_no ?? '-' }}</td>
                                <td class="font-monospace">{{ $ewb->delivery_no ?? ('DN #' . $ewb->delivery_note_id) }}</td>
                                <td>{{ $ewb->generated_date ?? '-' }}</td>
                                <td>{{ $ewb->valid_until ?? '-' }}</td>
                                <td>{{ $ewb->distance_km }} KM</td>
                                <td class="text-center pe-4">
                                    <span class="badge {{ $ewb->status === 'GENERATED' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-3 py-1">
                                        {{ $ewb->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-truck fs-3 d-block mb-2 opacity-50"></i>
                                    No E-Way bills generated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
