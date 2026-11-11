@extends('layouts.app')

@section('title', 'Inventory Valuation')
@section('heading', 'Inventory Valuation')
@section('subheading', 'Current stock value at cost and retail prices')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-green"><i class="bi bi-boxes"></i></div>
                <div>
                    <div class="stat-value">{{ $totals['total_stock'] }}</div>
                    <div class="stat-label">Total Units</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-primary"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-value">{{ money($totals['cost_value']) }}</div>
                    <div class="stat-label">Value at Cost</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-gold"><i class="bi bi-tags"></i></div>
                <div>
                    <div class="stat-value">{{ money($totals['retail_value']) }}</div>
                    <div class="stat-label">Value at Retail</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-rose"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="stat-value">{{ money($totals['potential_profit']) }}</div>
                    <div class="stat-label">Potential Profit</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Per-Product Valuation</h6>
            <a href="{{ route('reports.inventory.export') }}" class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Cost Value</th>
                        <th class="text-end">Retail Value</th>
                        <th class="text-end">Potential Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="small text-muted">{{ $row->sku }}</td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td>{{ $row->brand ?? '—' }}</td>
                            <td class="text-end">{{ $row->total_stock }}</td>
                            <td class="text-end">{{ money($row->cost_value) }}</td>
                            <td class="text-end">{{ money($row->retail_value) }}</td>
                            <td class="text-end fw-semibold text-success">{{ money((float) $row->retail_value - (float) $row->cost_value) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state"><i class="bi bi-box-seam"></i><p>No products in inventory.</p></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
