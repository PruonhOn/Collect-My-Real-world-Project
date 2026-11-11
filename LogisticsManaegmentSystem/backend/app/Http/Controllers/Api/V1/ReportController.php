<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregates behind `report.view`. Each endpoint answers one
 * question the business asks of its data: where the cargo is, how the
 * operation is moving, what customers owe, what carriers cost, and where
 * the money went.
 *
 * Nothing here is paginated: a report is a single query result, not a screen
 * somebody pages through. The export endpoint renders the same query as CSV.
 */
class ReportController extends Controller
{
    public function shipments(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Shipment::query();

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $rows = $query
            ->select([
                'status',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return ApiResponse::success($rows, 'Shipment report.');
    }

    public function operational(Request $request): JsonResponse
    {
        $pendingCustoms = Shipment::query()->where('status', 'customs')->count();
        $pendingDelivery = Shipment::query()->where('status', 'arrived')->count();

        return ApiResponse::success([
            'pending_customs' => $pendingCustoms,
            'pending_delivery' => $pendingDelivery,
        ], 'Operational report.');
    }

    public function customers(Request $request): JsonResponse
    {
        $rows = Shipment::query()
            ->join('parties AS shippers', 'shipments.shipper_id', '=', 'shippers.id')
            ->select([
                'shippers.name AS customer',
                'shippers.code AS code',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('shippers.id', 'shippers.name', 'shippers.code')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return ApiResponse::success($rows, 'Customer report.');
    }

    public function carriers(Request $request): JsonResponse
    {
        $rows = Shipment::query()
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select([
                'carriers.name AS carrier',
                'carriers.code AS code',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('carriers.id', 'carriers.name', 'carriers.code')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return ApiResponse::success($rows, 'Carrier report.');
    }

    public function financial(Request $request): JsonResponse
    {
        $outstanding = Invoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->sum(DB::raw('COALESCE(balance, total)'));

        $paid = Invoice::query()->where('status', 'paid')->sum('paid_amount');
        $revenue = Invoice::query()->where('status', 'paid')->sum('total');

        $expense = Expense::query()->where('counts_against_margin', true)->sum('amount');

        return ApiResponse::success([
            'outstanding_amount' => $outstanding,
            'revenue' => $revenue,
            'paid' => $paid,
            'expenses' => $expense,
            'profit' => max(0, $revenue - $expense),
        ], 'Financial report.');
    }

    public function export(Request $request, string $report): JsonResponse
    {
        $allowed = ['shipments', 'operational', 'customers', 'carriers', 'financial'];

        if (! in_array($report, $allowed, true)) {
            return ApiResponse::notFound("Report [{$report}] does not exist.");
        }

        $content = match ($report) {
            'shipments' => $this->exportShipments($request),
            'operational' => $this->exportOperational($request),
            'customers' => $this->exportCustomers($request),
            'carriers' => $this->exportCarriers($request),
            'financial' => $this->exportFinancial($request),
            default => '',
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$report}.csv\"",
        ];

        return response($content, 200, $headers);
    }

    private function exportShipments(Request $request): string
    {
        $rows = Shipment::query()
            ->select(['reference', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return $this->toCsv(['reference', 'status', 'created_at'], $rows);
    }

    private function exportOperational(Request $request): string
    {
        $rows = collect([
            ['metric' => 'Pending customs', 'count' => Shipment::query()->where('status', 'customs')->count()],
            ['metric' => 'Pending delivery', 'count' => Shipment::query()->where('status', 'arrived')->count()],
        ]);

        return $this->toCsv(['metric', 'count'], $rows);
    }

    private function exportCustomers(Request $request): string
    {
        $rows = Shipment::query()
            ->join('parties AS shippers', 'shipments.shipper_id', '=', 'shippers.id')
            ->select([
                'shippers.name AS customer',
                'shippers.code AS code',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('shippers.id', 'shippers.name', 'shippers.code')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return $this->toCsv(['customer', 'code', 'total'], $rows);
    }

    private function exportCarriers(Request $request): string
    {
        $rows = Shipment::query()
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select([
                'carriers.name AS carrier',
                'carriers.code AS code',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('carriers.id', 'carriers.name', 'carriers.code')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return $this->toCsv(['carrier', 'code', 'total'], $rows);
    }

    private function exportFinancial(Request $request): string
    {
        $rows = collect([
            ['metric' => 'Outstanding invoices', 'amount' => Invoice::query()->whereIn('status', ['issued', 'partially_paid'])->sum(DB::raw('COALESCE(balance, total)'))],
            ['metric' => 'Revenue', 'amount' => Invoice::query()->where('status', 'paid')->sum('total')],
            ['metric' => 'Expenses', 'amount' => Expense::query()->where('counts_against_margin', true)->sum('amount')],
        ]);

        return $this->toCsv(['metric', 'amount'], $rows);
    }

    private function toCsv(array $headers, $rows): string
    {
        $out = [];
        $out[] = implode(',', $headers);

        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $values[] = $this->csvEscape($value);
            }
            $out[] = implode(',', $values);
        }

        return implode("\n", $out);
    }

    private function csvEscape(mixed $value): string
    {
        $string = (string) $value;

        if (str_contains($string, ',') || str_contains($string, '"') || str_contains($string, "\n")) {
            return '"'.str_replace('"', '""', $string).'"';
        }

        return $string;
    }
}
