<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'invoice', 'paymentMethod', 'receivedBy']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id') && $request->customer_id !== null) {
            $query->where('customer_id', $request->customer_id);
        }

        $payments = $query->latest()->paginate(15)->withQueryString();
        $customers = Customer::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['payments' => $payments, 'customers' => $customers, 'paymentMethods' => $paymentMethods]);
        }

        return view('payments.index', compact('payments', 'customers', 'paymentMethods'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $invoices = Invoice::where('status', '!=', 'paid')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        return view('payments.create', compact('customers', 'invoices', 'paymentMethods'));
    }

    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();
        $validated['payment_number'] = 'PAY-' . strtoupper(Str::random(10));
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['status'] = 'completed';
        $validated['received_by'] = auth()->id();

        $payment = Payment::create($validated);

        if (!empty($validated['invoice_id'])) {
            $invoice = Invoice::find($validated['invoice_id']);
            if ($invoice) {
                $invoice->increment('paid_amount', $validated['amount']);
                $invoice->decrement('balance_due', $validated['amount']);
                if ($invoice->fresh()->balance_due <= 0) {
                    $invoice->update(['status' => 'paid']);
                } else {
                    $invoice->update(['status' => 'partial']);
                }
            }
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Payment recorded successfully', 'payment' => $payment], 201);
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully');
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'invoice', 'paymentMethod', 'receivedBy']);

        if (request()->ajax()) {
            return response()->json(['payment' => $payment]);
        }

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $customers = Customer::where('is_active', true)->get();
        $invoices = Invoice::where('status', '!=', 'paid')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        return view('payments.edit', compact('payment', 'customers', 'invoices', 'paymentMethods'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'reference_number' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Payment updated successfully', 'payment' => $payment]);
        }

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully');
    }

    public function destroy(Payment $payment)
    {
        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $invoice->decrement('paid_amount', $payment->amount);
                $invoice->increment('balance_due', $payment->amount);
                $invoice->update(['status' => 'unpaid']);
            }
        }

        $payment->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Payment deleted successfully']);
        }

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully');
    }
}
