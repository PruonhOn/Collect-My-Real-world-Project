<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\BusinessInvoice;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data exports for the business workspace.
 *
 * Every export is generated from the active tenant scope only. CSV is a
 * limited Free-plan entitlement, while Excel workbooks require the Pro
 * `pdf_excel_exports` feature.
 */
class ExportController extends Controller
{
    /** Maximum number of rows that may be exported in a single request. */
    public const MAX_ROWS = 5000;

    public function __construct(protected FeatureGate $gate) {}

    public function index(Request $request): JsonResponse
    {
        $business = app('current.business');

        return response()->json([
            'data' => [
                'resources' => ['products', 'customers', 'orders', 'invoices'],
                'formats' => ['csv', 'xlsx'],
                'can_export_excel' => $this->gate->allows($business, 'pdf_excel_exports'),
                'max_rows' => self::MAX_ROWS,
            ],
        ]);
    }

    public function show(Request $request, string $resource): StreamedResponse|JsonResponse
    {
        $business = app('current.business');

        if (! $this->gate->allows($business, 'limited_exports') && ! $this->gate->allows($business, 'pdf_excel_exports')) {
            return response()->json([
                'message' => 'Exports are not available on your current plan.',
                'upgrade_required' => true,
            ], 403);
        }

        $format = strtolower((string) $request->query('format', 'csv'));

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            return response()->json(['message' => 'Unsupported export format.'], 422);
        }

        if ($format === 'xlsx' && ! $this->gate->allows($business, 'pdf_excel_exports')) {
            return response()->json([
                'message' => 'Excel exports require a Pro subscription.',
                'upgrade_required' => true,
            ], 403);
        }

        $dataset = $this->dataset($request, $resource, $business);

        if ($dataset === null) {
            return response()->json(['message' => 'Unknown export resource.'], 404);
        }

        $filename = sprintf('%s-%s.%s', $resource, now()->format('Ymd-His'), $format);

        if ($format === 'xlsx') {
            return $this->xlsxResponse($dataset, $filename);
        }

        return $this->csvResponse($dataset, $filename);
    }

    /**
     * Build the tenant scoped dataset for the requested resource.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}|null
     */
    protected function dataset(Request $request, string $resource, $business): ?array
    {
        $limit = min(self::MAX_ROWS, max(1, (int) $request->query('limit', 1000)));

        return match ($resource) {
            'products' => [
                'headers' => ['SKU', 'Name', 'Category', 'Price', 'Status', 'Stock', 'Created'],
                'rows' => Product::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->with('category:id,name')
                    ->orderBy('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Product $p) => [
                        $p->sku,
                        $p->name,
                        $p->category?->name,
                        $this->money($p->price, $business->currency),
                        $p->status,
                        $p->track_inventory ? $p->stock_quantity : null,
                        $p->created_at?->toDateString(),
                    ])->all(),
            ],
            'customers' => [
                'headers' => ['First name', 'Last name', 'Email', 'Phone', 'City', 'Created'],
                'rows' => Customer::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->orderBy('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Customer $c) => [
                        $c->first_name,
                        $c->last_name,
                        $c->email,
                        $c->phone,
                        $c->city,
                        $c->created_at?->toDateString(),
                    ])->all(),
            ],
            'orders' => [
                'headers' => ['Order', 'Date', 'Customer', 'Status', 'Payment', 'Subtotal', 'Tax', 'Discount', 'Total', 'Currency'],
                'rows' => Order::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->with('customer:id,first_name,last_name')
                    ->orderBy('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Order $o) => [
                        $o->order_number,
                        $o->created_at?->toDateString(),
                        trim(($o->customer?->first_name ?? '').' '.($o->customer?->last_name ?? '')),
                        $o->status,
                        $o->payment_status,
                        $this->money($o->subtotal, $o->currency),
                        $this->money($o->tax, $o->currency),
                        $this->money($o->discount, $o->currency),
                        $this->money($o->total, $o->currency),
                        $o->currency,
                    ])->all(),
            ],
            'invoices' => [
                'headers' => ['Invoice', 'Date', 'Due date', 'Customer', 'Status', 'Subtotal', 'Tax', 'Total', 'Currency'],
                'rows' => BusinessInvoice::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->with('customer:id,first_name,last_name')
                    ->orderBy('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (BusinessInvoice $i) => [
                        $i->invoice_number,
                        $i->issue_date?->toDateString(),
                        $i->due_date?->toDateString(),
                        trim(($i->customer?->first_name ?? '').' '.($i->customer?->last_name ?? '')),
                        $i->status,
                        $this->money($i->subtotal, $i->currency),
                        $this->money($i->tax, $i->currency),
                        $this->money($i->total, $i->currency),
                        $i->currency,
                    ])->all(),
            ],
            default => null,
        };
    }

    protected function csvResponse(array $dataset, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($dataset) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $dataset['headers']);

            foreach ($dataset['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function xlsxResponse(array $dataset, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($dataset['headers'], null, 'A1');

        $row = 2;
        foreach ($dataset['rows'] as $values) {
            $sheet->fromArray($values, null, 'A'.$row);
            $row++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function money(int|float|null $amount, string $currency): string
    {
        return $currency.' '.number_format(((float) ($amount ?? 0)) / 100, 2);
    }
}
