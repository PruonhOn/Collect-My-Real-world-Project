<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\BusinessInvoice;
use App\Models\Order;
use App\Services\Pdf\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BusinessInvoice::query()->with('customer', 'order');

        if ($search = $request->query('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(min(100, (int) $request->query('per_page', 15))));
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('business_id', $business->id),
            ],
            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where('business_id', $business->id),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'unpaid', 'paid', 'overdue', 'canceled'])],
        ]);

        $invoice = DB::transaction(function () use ($validated, $business) {
            $subtotal = 0;
            $lines = [];

            foreach ($validated['items'] as $item) {
                $amount = (int) $item['unit_amount'] * (int) $item['quantity'];
                $subtotal += $amount;
                $lines[] = [
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                    'unit_amount' => (int) $item['unit_amount'],
                    'amount' => $amount,
                ];
            }

            $discount = (int) ($validated['discount'] ?? 0);
            $tax = (int) ($validated['tax'] ?? 0);
            $total = max(0, $subtotal - $discount) + $tax;

            $sequence = BusinessInvoice::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->count() + 1;

            $isBranded = app(\App\Services\Features\FeatureGate::class)->allows($business, 'branded_invoices');

            return BusinessInvoice::create([
                'business_id' => $business->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'order_id' => $validated['order_id'] ?? null,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'currency' => $business->currency,
                'status' => $validated['status'] ?? 'unpaid',
                'issue_date' => now()->toDateString(),
                'due_date' => $validated['due_date'] ?? null,
                'line_items' => $lines,
                'is_branded' => $isBranded,
            ]);
        });

        return response()->json(['data' => $invoice->load('customer')], 201);
    }

    public function show(BusinessInvoice $invoice): JsonResponse
    {
        $this->authorizeSameBusiness($invoice);

        return response()->json(['data' => $invoice->load('customer', 'order')]);
    }

    public function update(Request $request, BusinessInvoice $invoice): JsonResponse
    {
        $this->authorizeSameBusiness($invoice);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'unpaid', 'paid', 'overdue', 'canceled'])],
            'due_date' => ['nullable', 'date'],
        ]);

        if (($validated['status'] ?? null) === 'paid' && $invoice->status !== 'paid') {
            $validated['paid_at'] = now();
        }

        $invoice->update($validated);

        return response()->json(['data' => $invoice->fresh()]);
    }

    public function destroy(BusinessInvoice $invoice): JsonResponse
    {
        $this->authorizeSameBusiness($invoice);

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted.']);
    }

    public function pdf(BusinessInvoice $invoice): JsonResponse
    {
        $this->authorizeSameBusiness($invoice);

        $pdf = app(InvoicePdfService::class)->render($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
        ]);
    }

    protected function authorizeSameBusiness(BusinessInvoice $invoice): void
    {
        $business = app('current.business');
        if ($invoice->business_id !== $business->id) {
            abort(404, 'Invoice not found.');
        }
    }
}
