<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'shipment']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id') && $request->customer_id !== null) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $customers = Customer::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['invoices' => $invoices, 'customers' => $customers]);
        }

        return view('invoices.index', compact('invoices', 'customers'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $shipments = Shipment::latest()->get();
        return view('invoices.create', compact('customers', 'shipments'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();
        $validated['invoice_number'] = 'INV-' . strtoupper(Str::random(10));
        $validated['status'] = 'unpaid';
        $validated['paid_amount'] = 0;
        $validated['balance_due'] = $validated['total_amount'];

        $invoice = Invoice::create($validated);

        if ($request->has('items')) {
            foreach ($request->items as $item) {
                $invoice->invoiceItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Invoice created successfully', 'invoice' => $invoice], 201);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'shipment', 'invoiceItems', 'payments']);

        if (request()->ajax()) {
            return response()->json(['invoice' => $invoice]);
        }

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $customers = Customer::where('is_active', true)->get();
        $shipments = Shipment::latest()->get();
        $invoice->load('invoiceItems');
        return view('invoices.edit', compact('invoice', 'customers', 'shipments'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string|max:2000',
        ]);

        $validated['tax_amount'] = $validated['subtotal'] * ($validated['tax_rate'] / 100);
        $validated['balance_due'] = $validated['total_amount'] - $invoice->paid_amount;

        $invoice->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Invoice deleted successfully']);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully');
    }

    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'shipment', 'invoiceItems', 'payments']);

        return view('invoices.pdf', compact('invoice'));
    }
}
