<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reporting)
    {
    }

    public function sales(Request $request): View
    {
        $this->authorize('viewSales', ReportPolicy::class);

        $from = Carbon::parse($request->input('date_from', Carbon::today()->subDays(29)->format('Y-m-d')));
        $to = Carbon::parse($request->input('date_to', Carbon::today()->format('Y-m-d')));
        $cashierId = $request->filled('cashier_id') ? $request->integer('cashier_id') : null;
        $customerId = $request->filled('customer_id') ? $request->integer('customer_id') : null;

        $data = $this->reporting->salesReport($from, $to, $cashierId, $customerId);

        $cashiers = User::query()->where('is_active', true)->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();

        return view('reports.sales', array_merge($data, compact('from', 'to', 'cashiers', 'customers', 'cashierId', 'customerId')));
    }

    public function exportSales(Request $request): StreamedResponse
    {
        $this->authorize('export', ReportPolicy::class);

        $from = Carbon::parse($request->input('date_from', Carbon::today()->subDays(29)->format('Y-m-d')));
        $to = Carbon::parse($request->input('date_to', Carbon::today()->format('Y-m-d')));
        $cashierId = $request->filled('cashier_id') ? $request->integer('cashier_id') : null;
        $customerId = $request->filled('customer_id') ? $request->integer('customer_id') : null;

        $data = $this->reporting->salesReport($from, $to, $cashierId, $customerId);

        $filename = 'sales-report-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, function ($write) use ($data) {
            $write(['Order #', 'Date', 'Cashier', 'Customer', 'Items', 'Subtotal', 'Discount', 'Tax', 'Total', 'Refunded', 'Net', 'Payment', 'Status']);

            foreach ($data['orders'] as $order) {
                $write([
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->user?->name ?? '—',
                    $order->customer?->name ?? 'Walk-in',
                    $order->item_count,
                    number_format((float) $order->subtotal, 2),
                    number_format((float) $order->discount_amount, 2),
                    number_format((float) $order->tax_amount, 2),
                    number_format((float) $order->total, 2),
                    number_format((float) $order->refunded_amount, 2),
                    number_format((float) $order->total - (float) $order->refunded_amount, 2),
                    $order->payment_method?->label(),
                    Str::title(str_replace('_', ' ', $order->status->value)),
                ]);
            }

            $write([]);
            $write(['TOTALS', '', '', '', $data['itemCount'], '', '', '', number_format($data['grossTotal'], 2), number_format($data['refundedTotal'], 2), number_format($data['netTotal'], 2), '', '']);
        });
    }

    public function inventoryValuation(): View
    {
        $this->authorize('viewInventory', ReportPolicy::class);

        $data = $this->reporting->inventoryValuation();

        return view('reports.inventory', $data);
    }

    public function exportInventory(): StreamedResponse
    {
        $this->authorize('export', ReportPolicy::class);

        $data = $this->reporting->inventoryValuation();

        return $this->streamCsv('inventory-valuation-'.now()->format('Y-m-d').'.csv', function ($write) use ($data) {
            $write(['SKU', 'Product', 'Brand', 'Units in stock', 'Cost value', 'Retail value']);

            foreach ($data['rows'] as $row) {
                $write([
                    $row->sku,
                    $row->name,
                    $row->brand ?? '—',
                    $row->total_stock,
                    number_format((float) $row->cost_value, 2),
                    number_format((float) $row->retail_value, 2),
                ]);
            }

            $write([]);
            $write(['TOTAL', '', '', $data['totals']['total_stock'], number_format($data['totals']['cost_value'], 2), number_format($data['totals']['retail_value'], 2)]);
        });
    }

    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $writer(function (array $row) use ($handle) {
                fputcsv($handle, $row);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
