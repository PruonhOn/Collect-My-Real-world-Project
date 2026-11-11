@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports')

@push('styles')
<style>
    .export-btns .btn { margin-right: 0.25rem; }
    .summary-stat { font-size: 1.25rem; font-weight: 600; }
</style>
@endpush

@section('content')
<ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab">
            <i class="bi bi-calendar-day me-1"></i>Daily Sales
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">
            <i class="bi bi-calendar-month me-1"></i>Monthly Sales
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
            <i class="bi bi-box me-1"></i>Product Performance
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
            <i class="bi bi-boxes me-1"></i>Inventory Report
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab">
            <i class="bi bi-people me-1"></i>Customer Report
        </button>
    </li>
</ul>

<div class="tab-content" id="reportTabContent">
    <div class="tab-pane fade show active" id="daily" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">Date</label>
                        <input type="date" id="dailyDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-auto d-flex align-items-end gap-1">
                        <button class="btn btn-sm btn-primary" onclick="loadDailyReport()"><i class="bi bi-search me-1"></i>Generate</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReport('dailyReportContent')"><i class="bi bi-printer"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="exportPDF('dailyReportContent')"><i class="bi bi-filetype-pdf"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="dailyReportContent">
                <div class="row g-3 mb-3" id="dailySummary">
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Total Sales</small>
                            <div class="summary-stat text-primary" id="dailyTotalSales">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Total Revenue</small>
                            <div class="summary-stat text-success" id="dailyTotalRevenue">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Total Items</small>
                            <div class="summary-stat text-info" id="dailyTotalItems">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Avg Sale Value</small>
                            <div class="summary-stat text-warning" id="dailyAvgSale">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dailyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th class="text-end">Items</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="dailyTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-4">Select a date and click Generate.</td></tr>
                        </tbody>
                        <tfoot id="dailyTableFoot" class="table-secondary d-none">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                                <td class="text-end fw-semibold" id="dailyGrandTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="monthly" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">Year</label>
                        <select id="monthlyYear" class="form-select form-select-sm">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">Month</label>
                        <select id="monthlyMonth" class="form-select form-select-sm">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ sprintf('%02d', $m) == date('m') ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end gap-1">
                        <button class="btn btn-sm btn-primary" onclick="loadMonthlyReport()"><i class="bi bi-search me-1"></i>Generate</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReport('monthlyReportContent')"><i class="bi bi-printer"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="exportPDF('monthlyReportContent')"><i class="bi bi-filetype-pdf"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="monthlyReportContent">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="monthlyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Sales Count</th>
                                <th class="text-end">Items Sold</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="monthlyTableBody">
                            <tr><td colspan="4" class="text-center text-muted py-4">Select year/month and click Generate.</td></tr>
                        </tbody>
                        <tfoot id="monthlyTableFoot" class="table-secondary d-none">
                            <tr>
                                <td class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-semibold" id="monthlyTotalCount">0</td>
                                <td class="text-end fw-semibold" id="monthlyTotalItems">0</td>
                                <td class="text-end fw-semibold" id="monthlyTotalRevenue">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="products" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">From</label>
                        <input type="date" id="prodFrom" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">To</label>
                        <input type="date" id="prodTo" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-auto d-flex align-items-end gap-1">
                        <button class="btn btn-sm btn-primary" onclick="loadProductReport()"><i class="bi bi-search me-1"></i>Generate</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReport('productReportContent')"><i class="bi bi-printer"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="exportPDF('productReportContent')"><i class="bi bi-filetype-pdf"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="productReportContent">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="productTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th class="text-end">Quantity Sold</th>
                                <th class="text-end">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Select date range and click Generate.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="inventory" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-boxes me-1"></i>Stock Status</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReport('inventoryReportContent')"><i class="bi bi-printer"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="exportPDF('inventoryReportContent')"><i class="bi bi-filetype-pdf"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="inventoryReportContent">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Total Products</small>
                            <div class="summary-stat" id="invTotalProducts">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">In Stock</small>
                            <div class="summary-stat text-success" id="invInStock">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Low Stock</small>
                            <div class="summary-stat text-warning" id="invLowStock">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <small class="text-muted">Out of Stock</small>
                            <div class="summary-stat text-danger" id="invOutOfStock">0</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="inventoryTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Min Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Loading inventory data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="customer" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">From</label>
                        <input type="date" id="custFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">To</label>
                        <input type="date" id="custTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto d-flex align-items-end gap-1">
                        <button class="btn btn-sm btn-primary" onclick="loadCustomerReport()"><i class="bi bi-search me-1"></i>Generate</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReport('customerReportContent')"><i class="bi bi-printer"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="exportPDF('customerReportContent')"><i class="bi bi-filetype-pdf"></i></button>
                    </div>
                </div>
            </div>
            <div class="card-body" id="customerReportContent">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="customerTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-end">Total Purchases</th>
                                <th class="text-end">Total Spent</th>
                                <th class="text-end">Points</th>
                                <th>Last Purchase</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <tr><td colspan="8" class="text-center text-muted py-4">Select date range and click Generate.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        loadDailyReport();
        loadInventoryReport();
    });

    function loadDailyReport() {
        const date = $('#dailyDate').val();
        $.get('{{ route("reports.daily") }}', { date: date }, function (data) {
            $('#dailyTotalSales').text(data.total_sales || 0);
            $('#dailyTotalRevenue').text(parseFloat(data.total_revenue || 0).toFixed(2));
            $('#dailyTotalItems').text(data.total_items || 0);
            $('#dailyAvgSale').text(parseFloat(data.avg_sale || 0).toFixed(2));

            let rows = '';
            if (data.sales && data.sales.length) {
                data.sales.forEach(function (s) {
                    rows += `<tr>
                        <td><code>${s.invoice_no}</code></td>
                        <td>${s.time || '—'}</td>
                        <td>${s.customer || 'Walk-in'}</td>
                        <td class="text-end">${s.items || 0}</td>
                        <td class="text-end fw-semibold">${parseFloat(s.total).toFixed(2)}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="5" class="text-center text-muted py-4">No sales found for this date.</td></tr>';
            }
            $('#dailyTableBody').html(rows);
            $('#dailyGrandTotal').text(parseFloat(data.total_revenue || 0).toFixed(2));
            $('#dailyTableFoot').toggleClass('d-none', !(data.sales && data.sales.length));
        });
    }

    function loadMonthlyReport() {
        const year = $('#monthlyYear').val();
        const month = $('#monthlyMonth').val();
        $.get('{{ route("reports.monthly") }}', { year: year, month: month }, function (data) {
            let rows = '';
            let totalCount = 0, totalItems = 0, totalRevenue = 0;
            if (data.sales && data.sales.length) {
                data.sales.forEach(function (s) {
                    rows += `<tr>
                        <td>${s.date}</td>
                        <td class="text-end">${s.count}</td>
                        <td class="text-end">${s.items}</td>
                        <td class="text-end fw-semibold">${parseFloat(s.revenue).toFixed(2)}</td>
                    </tr>`;
                    totalCount += s.count;
                    totalItems += s.items;
                    totalRevenue += parseFloat(s.revenue);
                });
            } else {
                rows = '<tr><td colspan="4" class="text-center text-muted py-4">No sales found for this period.</td></tr>';
            }
            $('#monthlyTableBody').html(rows);
            $('#monthlyTotalCount').text(totalCount);
            $('#monthlyTotalItems').text(totalItems);
            $('#monthlyTotalRevenue').text(totalRevenue.toFixed(2));
            $('#monthlyTableFoot').toggleClass('d-none', !(data.sales && data.sales.length));
        });
    }

    function loadProductReport() {
        const from = $('#prodFrom').val();
        const to = $('#prodTo').val();
        $.get('{{ route("reports.products") }}', { from: from, to: to }, function (data) {
            let rows = '';
            if (data.products && data.products.length) {
                data.products.forEach(function (p, i) {
                    rows += `<tr>
                        <td>${i + 1}</td>
                        <td class="fw-semibold">${p.name}</td>
                        <td><code>${p.sku || '—'}</code></td>
                        <td>${p.category || '—'}</td>
                        <td class="text-end">${p.quantity_sold || 0}</td>
                        <td class="text-end fw-semibold">${parseFloat(p.revenue || 0).toFixed(2)}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="6" class="text-center text-muted py-4">No product data found for this period.</td></tr>';
            }
            $('#productTableBody').html(rows);
        });
    }

    function loadInventoryReport() {
        $.get('{{ route("reports.inventory") }}', function (data) {
            $('#invTotalProducts').text(data.total_products || 0);
            $('#invInStock').text(data.in_stock || 0);
            $('#invLowStock').text(data.low_stock || 0);
            $('#invOutOfStock').text(data.out_of_stock || 0);

            let rows = '';
            if (data.products && data.products.length) {
                data.products.forEach(function (p, i) {
                    let badge = '<span class="badge bg-success">In Stock</span>';
                    if (p.stock <= 0) badge = '<span class="badge bg-danger">Out of Stock</span>';
                    else if (p.stock <= (p.min_stock || 5)) badge = '<span class="badge bg-warning text-dark">Low Stock</span>';
                    rows += `<tr>
                        <td>${i + 1}</td>
                        <td class="fw-semibold">${p.name}</td>
                        <td><code>${p.sku || '—'}</code></td>
                        <td class="text-end">${p.stock}</td>
                        <td class="text-end">${p.min_stock || 0}</td>
                        <td>${badge}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="6" class="text-center text-muted py-4">No inventory data available.</td></tr>';
            }
            $('#inventoryTableBody').html(rows);
        });
    }

    function loadCustomerReport() {
        const from = $('#custFrom').val();
        const to = $('#custTo').val();
        $.get('{{ route("reports.customers") }}', { from: from, to: to }, function (data) {
            let rows = '';
            if (data.customers && data.customers.length) {
                data.customers.forEach(function (c, i) {
                    rows += `<tr>
                        <td>${i + 1}</td>
                        <td class="fw-semibold">${c.name}</td>
                        <td>${c.email || '—'}</td>
                        <td>${c.phone || '—'}</td>
                        <td class="text-end">${c.total_purchases || 0}</td>
                        <td class="text-end fw-semibold">${parseFloat(c.total_spent || 0).toFixed(2)}</td>
                        <td class="text-end"><span class="badge bg-warning text-dark">${c.points || 0}</span></td>
                        <td>${c.last_purchase_date || '—'}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="8" class="text-center text-muted py-4">No customer data found for this period.</td></tr>';
            }
            $('#customerTableBody').html(rows);
        });
    }

    function printReport(elementId) {
        const content = document.getElementById(elementId).innerHTML;
        const win = window.open('', '_blank');
        win.document.write('<html><head><title>Report</title>');
        win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        win.document.write('<style>body { padding: 20px; }</style>');
        win.document.write('</head><body>' + content + '</body></html>');
        win.document.close();
        win.print();
    }

    function exportPDF(elementId) {
        const { jsPDF } = window.jspdf;
        const content = document.getElementById(elementId);
        if (typeof jsPDF === 'undefined') {
            alert('PDF export requires jsPDF library. Loading...');
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            document.head.appendChild(script);
            script.onload = function () {
                html2canvas(content).then(function (canvas) {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('l', 'mm', 'a4');
                    const imgWidth = 280;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    pdf.addImage(imgData, 'PNG', 5, 10, imgWidth, imgHeight);
                    pdf.save('report.pdf');
                });
            };
            const h2c = document.createElement('script');
            h2c.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            document.head.appendChild(h2c);
            return;
        }
        html2canvas(content).then(function (canvas) {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('l', 'mm', 'a4');
            const imgWidth = 280;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            pdf.addImage(imgData, 'PNG', 5, 10, imgWidth, imgHeight);
            pdf.save('report.pdf');
        });
    }
</script>
@endpush
