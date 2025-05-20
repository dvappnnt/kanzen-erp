<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePaymentMethodDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoicePaymentMethodDetailController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = InvoicePaymentMethodDetail::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function store(Request $request, Invoice $invoice)
    {
        // Only allow payments for credit invoices
        if (!$invoice->is_credit) {
            return response()->json([
                'message' => 'Payments can only be added to credit invoices.'
            ], 422);
        }

        $validated = $request->validate([
            'company_account_id' => 'nullable|exists:company_accounts,id',
            'payment_method' => 'required|string|in:cash,bank-transfer,credit-card,gcash,check',
            'reference_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'status' => 'required|string|in:pending,approved,rejected',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('payment-files', 'public');
            $validated['receipt_attachment'] = $path;
        }

        $validated['invoice_id'] = $invoice->id;
        $model = $this->modelClass::create($validated);

        return response()->json([
            'data' => $model,
            'message' => 'Payment added successfully.'
        ], 201);
    }

    public function update(Request $request, Invoice $invoice, InvoicePaymentMethodDetail $payment)
    {
        // Only allow updates for pending payments
        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only update payments in pending status.'
            ], 422);
        }

        $validated = $request->validate([
            'company_account_id' => 'nullable|exists:company_accounts,id',
            'payment_method' => 'required|string|in:cash,bank-transfer,credit-card,gcash,check',
            'reference_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($payment->receipt_attachment) {
                Storage::disk('public')->delete($payment->receipt_attachment);
            }
            
            $file = $request->file('file');
            $path = $file->store('payment-files', 'public');
            $validated['receipt_attachment'] = $path;
        }

        $payment->update($validated);

        return response()->json([
            'data' => $payment,
            'message' => 'Payment updated successfully.'
        ]);
    }

    public function destroy(Invoice $invoice, InvoicePaymentMethodDetail $payment)
    {
        // Only allow deletion if status is pending
        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only delete payments in pending status.'
            ], 422);
        }

        // Delete file if exists
        if ($payment->receipt_attachment) {
            Storage::disk('public')->delete($payment->receipt_attachment);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully.'
        ]);
    }

    public function approve(Request $request, Invoice $invoice, InvoicePaymentMethodDetail $payment)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve payments in pending status.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update payment status
            $payment->update([
                'status' => 'approved',
                'remarks' => $validated['remarks'] ?? null
            ]);

            // Calculate total approved payments
            $totalApprovedPayments = $invoice->paymentMethodDetails()
                ->where('status', 'approved')
                ->sum('amount');

            // Update invoice status based on total amount and approved payments
            if ($totalApprovedPayments >= $invoice->total_amount) {
                $invoice->update(['status' => 'fully-paid']);
            } elseif ($totalApprovedPayments > 0) {
                $invoice->update(['status' => 'partially-paid']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Payment approved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error approving payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, Invoice $invoice, InvoicePaymentMethodDetail $payment)
    {
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only reject payments in pending status.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update payment status
            $payment->update([
                'status' => 'rejected',
                'remarks' => $validated['remarks']
            ]);

            // Calculate total approved payments
            $totalApprovedPayments = $invoice->paymentMethodDetails()
                ->where('status', 'approved')
                ->sum('amount');

            // Update invoice status based on total amount and approved payments
            if ($totalApprovedPayments >= $invoice->total_amount) {
                $invoice->update(['status' => 'fully-paid']);
            } elseif ($totalApprovedPayments > 0) {
                $invoice->update(['status' => 'partially-paid']);
            } else {
                $invoice->update(['status' => 'unpaid']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Payment rejected successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error rejecting payment: ' . $e->getMessage()
            ], 500);
        }
    }
} 