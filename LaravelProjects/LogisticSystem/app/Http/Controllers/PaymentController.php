<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected InvoiceService $invoiceService,
        protected CustomerService $customerService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $payments = $this->paymentService->getAll($request->only([
            'invoice_id', 'customer_id', 'payment_method', 'search',
        ]));
        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $invoiceId = $request->get('invoice_id');
        $invoice = $invoiceId ? $this->invoiceService->getById($invoiceId) : null;
        $invoices = $this->invoiceService->getAll(['status' => 'unpaid']);
        return view('payments.create', compact('invoice', 'invoices', 'invoiceId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $payment = $this->paymentService->create($validated);

            $this->invoiceService->markAsPaid($validated['invoice_id'], $validated);

            $this->auditLogService->log('created', 'payments', "Payment created: {$payment->payment_number}", $payment, null, $validated, auth()->id());

            return redirect()->route('payments.index')
                ->with('success', "Payment {$payment->payment_number} recorded successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $payment = $this->paymentService->getById($id);
        return view('payments.show', compact('payment'));
    }

    public function edit(int $id): View
    {
        $payment = $this->paymentService->getById($id);
        $invoices = $this->invoiceService->getAll(['status' => 'unpaid']);
        return view('payments.edit', compact('payment', 'invoices'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            $payment = $this->paymentService->update($id, $validated);

            $this->auditLogService->log('updated', 'payments', "Payment updated: {$payment->payment_number}", $payment, null, $validated, auth()->id());

            return redirect()->route('payments.index')
                ->with('success', "Payment {$payment->payment_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update payment: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $payment = $this->paymentService->getById($id);
            $this->paymentService->delete($id);

            $this->auditLogService->log('deleted', 'payments', "Payment deleted: {$payment->payment_number}", $payment, null, null, auth()->id());

            return redirect()->route('payments.index')
                ->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }
}
