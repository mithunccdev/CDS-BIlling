@php
    $workOrders = \Illuminate\Support\Facades\Schema::hasTable('work_orders')
        ? DB::table('work_orders as wo')
            ->leftJoin('inventory_items as item', 'wo.item_id', '=', 'item.id')
            ->leftJoin('boms as bom', 'wo.bom_id', '=', 'bom.id')
            ->select('wo.*', 'item.name as item_name', 'item.unit_price', 'bom.bom_no')
            ->orderByDesc('wo.id')
            ->get()
        : collect();
@endphp

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-cpu-fill me-2 text-primary"></i>MRP & Production Costing (WIP)</h4>
            <p class="text-muted small mb-0">Material Requirements Planning, raw material issues to WIP, scrap tracking & finished goods receipt</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-light">
        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Material Requirements Planning Engine Summary</h6>
        <p class="text-muted small mb-0">The MRP engine evaluates open Sales Orders, minimum reorder safety levels, and Bill of Materials component trees to plan production requirements.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Work In Progress (WIP) & Production Batches Log</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Work Order</th>
                            <th>Target Product</th>
                            <th>BOM Ref</th>
                            <th>Planned Qty</th>
                            <th class="text-end">Estimated Value (₹)</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workOrders as $wo)
                            @php
                                $estimatedValue = ((float) ($wo->qty ?? 0)) * ((float) ($wo->unit_price ?? 0));
                                $statusBadge = match($wo->status) {
                                    'Completed', 'Closed' => 'bg-success-subtle text-success',
                                    'In Progress', 'Released' => 'bg-warning-subtle text-warning',
                                    default => 'bg-secondary-subtle text-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-primary">{{ $wo->work_order_no }}</td>
                                <td>{{ $wo->item_name ?? 'N/A' }}</td>
                                <td class="font-monospace">{{ $wo->bom_no ?? '-' }}</td>
                                <td>{{ $wo->qty }}</td>
                                <td class="text-end font-monospace fw-bold">₹{{ number_format($estimatedValue, 2) }}</td>
                                <td class="text-center pe-4"><span class="badge {{ $statusBadge }} rounded-pill px-3 py-1">{{ $wo->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-cpu fs-3 d-block mb-2 opacity-50"></i>
                                    No production work orders recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
