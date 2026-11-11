<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected CustomerService $customerService,
        protected ShipmentService $shipmentService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $invoices = $this->invoiceService->getAll($request->only([
            'search', 'status', 'customer_id', 'branch_id', 'date_from', 'date_to',
        ]));
        $statuses = $this->invoiceService->getStatuses();
        return view('invoices.index', compact('invoices', 'statuses'));
    }

    public function create(): View
    {
        $customers = $this->customerService->getAll(['is_active' => true]);
        $shipments = [];
        $branches = $this->branchService->getActiveBranches();
        $statuses = $this->invoiceService->getStatuses();
        return view('invoices.create', compact('customers', 'shipments', 'branches', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'required|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.type' => 'nullable|string|max:50',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.total' => 'required_with:items|numeric|min:0',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $invoice = $this->invoiceService->create($validated);

            $this->auditLogService->log('created', 'invoices', "Invoice created: {$invoice->invoice_number}", $invoice, null, $validated, auth()->id());

            return redirect()->route('invoices.index')
                ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $invoice = $this->invoiceService->getById($id);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(int $id): View
    {
        $invoice = $this->invoiceService->getById($id);
        $customers = $this->customerService->getAll(['is_active' => true]);
        $shipments = [];
        $branches = $this->branchService->getActiveBranches();
        $statuses = $this->invoiceService->getStatuses();
        return view('invoices.edit', compact('invoice', 'customers', 'shipments', 'branches', 'statuses'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'required|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.type' => 'nullable|string|max:50',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.total' => 'required_with:items|numeric|min:0',
        ]);

        try {
            $invoice = $this->invoiceService->update($id, $validated);

            $this->auditLogService->log('updated', 'invoices', "Invoice updated: {$invoice->invoice_number}", $invoice, null, $validated, auth()->id());

            return redirect()->route('invoices.index')
                ->with('success', "Invoice {$invoice->invoice_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $invoice = $this->invoiceService->getById($id);
            $this->invoiceService->delete($id);

            $this->auditLogService->log('deleted', 'invoices', "Invoice deleted: {$invoice->invoice_number}", $invoice, null, null, auth()->id());

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    public function downloadPdf(int $id)
    {
        try {
            $invoice = $this->invoiceService->getById($id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice'));
            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to download PDF: ' . $e->getMessage());
        }
    }

    public function markPaid(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->invoiceService->markAsPaid($id, $validated);

            $this->auditLogService->log('updated', 'invoices', "Invoice marked as paid", null, null, $validated, auth()->id());

            return back()->with('success', 'Invoice marked as paid successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark invoice: ' . $e->getMessage());
        }
    }
}
