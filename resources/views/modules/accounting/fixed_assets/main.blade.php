@php
    $assets = \Illuminate\Support\Facades\Schema::hasTable('fixed_assets')
        ? DB::table('fixed_assets')->orderByDesc('id')->get()
        : collect();

    $grossBlock = (float) $assets->sum('purchase_cost');
    $accumDepr = (float) $assets->sum('accumulated_depreciation');
    $netBookValue = (float) $assets->sum('current_book_value');
@endphp

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-building-gear text-success me-2"></i>Fixed Assets & Depreciation Register</h3>
            <p class="text-muted mb-0">Track capitalized company assets, useful life, and cumulative depreciation schedules.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="small text-muted text-uppercase fw-bold">Gross Asset Block</div>
                <div class="fs-3 fw-bolder text-primary mt-1">₹{{ number_format($grossBlock, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                <div class="small text-muted text-uppercase fw-bold">Accumulated Depreciation</div>
                <div class="fs-3 fw-bolder text-warning mt-1">₹{{ number_format($accumDepr, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="small text-muted text-uppercase fw-bold">Net Book Value</div>
                <div class="fs-3 fw-bolder text-success mt-1">₹{{ number_format($netBookValue, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Fixed Assets Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Capitalized Assets Registry</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Asset Code</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Purchase Cost</th>
                            <th>Method</th>
                            <th>Accum. Depr.</th>
                            <th>Net Book Value</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $asset)
                            @php
                                $statusBadge = match($asset->status) {
                                    'Active' => 'bg-success',
                                    'Sold' => 'bg-info',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">{{ $asset->asset_code }}</td>
                                <td class="fw-semibold">{{ $asset->name }}</td>
                                <td>{{ $asset->category }}</td>
                                <td>{{ $asset->purchase_date }}</td>
                                <td>₹{{ number_format((float)$asset->purchase_cost, 2) }}</td>
                                <td>{{ $asset->depreciation_method }} ({{ $asset->useful_life_years }}y)</td>
                                <td class="text-warning fw-semibold">₹{{ number_format((float)$asset->accumulated_depreciation, 2) }}</td>
                                <td class="fw-bold text-success">₹{{ number_format((float)$asset->current_book_value, 2) }}</td>
                                <td class="pe-3 text-end"><span class="badge {{ $statusBadge }}">{{ $asset->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-building-gear fs-3 d-block mb-2 opacity-50"></i>
                                    No fixed assets registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
