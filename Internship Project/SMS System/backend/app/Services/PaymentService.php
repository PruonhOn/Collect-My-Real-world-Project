<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\StudentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly FeeService $feeService,
        private readonly FileService $fileService,
    ) {}

    /**
     * Process a payment for a fee through the configured gateway and record
     * the Payment row. Refreshing the fee status happens in the same
     * transaction as the payment record.
     *
     * @param  array{amount: float|int|string, method?: string|null, note?: string|null, transaction_id?: string|null, gateway?: string|null}  $data
     */
    public function charge(StudentFee $fee, array $data, ?int $parentId = null, ?int $processedBy = null): Payment
    {
        $amount = (float) $data['amount'];
        $balance = $fee->balance();

        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        if ($amount > $balance + 0.0001) {
            throw new RuntimeException('Payment amount exceeds the outstanding balance.');
        }

        $gateway = $this->gatewayManager->driver($data['gateway'] ?? null);
        $result = $gateway->charge([
            'amount' => $amount,
            'parent_name' => $parentId !== null ? ($fee->student->guardian_name ?? 'Parent') : null,
            'student_name' => $fee->student?->user?->name ?? 'Student',
            'invoice_no' => $fee->invoice_no,
        ], $data['transaction_id'] ?? null);

        $payment = DB::transaction(function () use ($fee, $data, $amount, $result, $parentId, $processedBy, $gateway) {
            $payment = Payment::create([
                'student_fee_id' => $fee->getKey(),
                'student_id' => $fee->student_id,
                'parent_id' => $parentId,
                'processed_by' => $processedBy,
                'amount' => $amount,
                'method' => $data['method'] ?? 'manual',
                'gateway' => $gateway->identifier(),
                'transaction_id' => $result->transactionId,
                'status' => $result->status->value,
                'paid_on' => $result->status === PaymentStatus::Confirmed ? now() : null,
                'notes' => $data['note'] ?? null,
            ]);

            if ($result->status === PaymentStatus::Confirmed) {
                $this->feeService->refreshStatus($fee);
            }

            return $payment;
        });

        if ($result->status === PaymentStatus::Confirmed) {
            $this->issueReceipt($payment);
        }

        return $payment;
    }

    public function confirm(Payment $payment): Payment
    {
        $payment->status = PaymentStatus::Confirmed->value;
        $payment->paid_on = now();
        $payment->save();

        $this->feeService->refreshStatus($payment->studentFee);
        $this->issueReceipt($payment);

        return $payment;
    }

    public function refund(Payment $payment): Payment
    {
        $payment->status = PaymentStatus::Refunded->value;
        $payment->save();

        $this->feeService->refreshStatus($payment->studentFee);

        return $payment;
    }

    /**
     * Generate and attach a PDF receipt. Idempotent for a confirmed payment.
     */
    public function issueReceipt(Payment $payment): PaymentReceipt
    {
        if ($payment->receipt()->exists()) {
            return $payment->receipt;
        }

        if ($payment->status->value !== PaymentStatus::Confirmed->value) {
            throw new RuntimeException('Receipts can only be issued for confirmed payments.');
        }

        $html = view('receipts.payment', ['payment' => $payment])->render();
        $pdf = Pdf::loadHTML($html);
        $contents = $pdf->output();

        $file = $this->fileService->storeContent(
            $contents,
            'payment-'.$payment->id.'.pdf',
            'application/pdf',
            'receipt',
            $payment,
            $payment->processed_by,
        );

        return PaymentReceipt::create([
            'payment_id' => $payment->getKey(),
            'receipt_no' => $this->nextReceiptNumber(),
            'file_id' => $file->getKey(),
            'issued_at' => now(),
        ]);
    }

    public function nextReceiptNumber(): string
    {
        $year = now()->year;

        return sprintf('RCP-%d-%06d', $year, PaymentReceipt::max('id') + 1);
    }
}
