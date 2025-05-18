<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Invoice;
use App\Models\WarehouseProduct;
use App\Models\WarehouseProductSerial;
use App\Models\InvoiceSerial;

class InvoiceController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = Invoice::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['customer', 'company', 'warehouse', 'paymentMethodDetails'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'required|string|in:pos-invoice,sales-invoice',
            'invoice_date' => 'required|date',
            'discount_rate' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|string|in:draft,fully-paid',
            'is_credit' => [
                'required',
                'boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->type !== 'sales-invoice') {
                        $fail('Credit option is only available for sales invoices.');
                    }
                },
            ],
            'payment_method' => [
                Rule::requiredIf(function () use ($request) {
                    return !$request->is_credit && $request->type === 'sales-invoice';
                }),
                Rule::in(['cash', 'bank-transfer', 'credit-card', 'gcash']),
            ],
            'payment_details' => [
                Rule::requiredIf(function () use ($request) {
                    return !$request->is_credit && $request->type === 'sales-invoice';
                }),
                'array',
            ],
            'payment_details.reference_number' => [
                Rule::requiredIf(function () use ($request) {
                    return !$request->is_credit && $request->type === 'sales-invoice' && $request->payment_method !== 'cash';
                }),
                'nullable',
                'string',
            ],
            'payment_details.account_number' => 'nullable|string',
            'payment_details.account_name' => 'nullable|string',
            'payment_details.bank_id' => 'nullable|exists:banks,id',
            'payment_details.company_account_id' => 'nullable|exists:company_accounts,id',
            'receipt_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB max
            'items' => 'required|array',
            'items.*.warehouse_product_id' => 'required|exists:warehouse_products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.serials' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Create invoice
            $invoice = $this->modelClass::create([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => $validated['type'],
                'invoice_date' => $validated['invoice_date'],
                'payment_date' => $validated['type'] === 'pos-invoice' ? Carbon::now() : ($validated['status'] === 'fully-paid' ? Carbon::now() : null),
                'discount_rate' => $validated['discount_rate'],
                'discount_amount' => $validated['discount_amount'],
                'tax_rate' => $validated['tax_rate'],
                'tax_amount' => $validated['tax_amount'],
                'shipping_cost' => $validated['shipping_cost'],
                'subtotal' => $validated['subtotal'],
                'total_amount' => $validated['total_amount'],
                'currency' => 'PHP',
                'status' => $validated['status'],
                'is_credit' => $validated['type'] === 'sales-invoice' ? $validated['is_credit'] : false,
                'created_by_user_id' => Auth::id()
            ]);

            // Handle payment details for both POS and non-credit sales invoices
            if (($validated['type'] === 'pos-invoice' || !$validated['is_credit']) && isset($validated['payment_method'])) {
                $paymentDetails = $request->input('payment_details');
                
                // Handle file upload if exists
                if ($request->hasFile('receipt_attachment')) {
                    $file = $request->file('receipt_attachment');
                    $path = $file->store('receipts', 'public');
                    $paymentDetails['receipt_attachment'] = $path;
                }

                $invoice->paymentMethodDetails()->create([
                    'payment_method' => $validated['payment_method'],
                    'account_number' => $paymentDetails['account_number'] ?? null,
                    'account_name' => $paymentDetails['account_name'] ?? null,
                    'reference_number' => $paymentDetails['reference_number'] ?? null,
                    'bank_id' => $paymentDetails['bank_id'] ?? null,
                    'company_account_id' => $paymentDetails['company_account_id'] ?? null,
                    'receipt_attachment' => $paymentDetails['receipt_attachment'] ?? null,
                    'status' => 'approved',
                    'payment_date' => Carbon::now(),
                    'amount' => $validated['total_amount']
                ]);
            }

            // Create invoice details and handle serials
            foreach ($validated['items'] as $item) {
                $detail = $invoice->details()->create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'warehouse_product_id' => $item['warehouse_product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'currency' => 'PHP'
                ]);

                // Only deduct quantity from warehouse_product if status is fully-paid
                if ($validated['status'] === 'fully-paid') {
                    $warehouseProduct = WarehouseProduct::find($item['warehouse_product_id']);
                    if ($warehouseProduct) {
                        $warehouseProduct->decrement('qty', $item['qty']);
                    }
                }

                // Handle serials if present
                if (isset($item['serials']) && !empty($item['serials'])) {
                    foreach ($item['serials'] as $serialNumber) {
                        $serial = WarehouseProductSerial::where('serial_number', $serialNumber)
                            ->where('warehouse_product_id', $item['warehouse_product_id'])
                            ->where('is_sold', 0)
                            ->first();

                        if ($serial) {
                            $invoiceSerial = new InvoiceSerial([
                                'warehouse_product_id' => $item['warehouse_product_id'],
                                'warehouse_product_serial_id' => $serial->id,
                                'is_expired' => false,
                                'is_replaced' => false
                            ]);

                            $detail->invoiceSerials()->save($invoiceSerial);

                            // Only mark serial as sold if status is fully-paid
                            if ($validated['status'] === 'fully-paid') {
                                $serial->update(['is_sold' => 1]);
                            }
                        } else {
                            throw new \Exception("Serial number '{$serialNumber}' not found or already sold for this product.");
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'data' => $invoice->fresh(['details.warehouseProduct', 'paymentMethodDetails', 'customer', 'company']),
                'message' => "Invoice '{$invoice->number}' " . ($validated['status'] === 'draft' ? 'saved as draft' : 'created') . " successfully."
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['customer', 'company', 'warehouse', 'paymentMethodDetails', 'paymentMethodDetails.bank'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        $model->update($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ]);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->delete();

        return response()->json(['message' => "{$this->modelName} deleted successfully."], 200);
    }

    public function restore($id)
    {
        $model = $this->modelClass::withTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => "{$this->modelName} restored successfully."
        ], 200);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $searchTerm = $request->input('search');

        $models = $this->modelClass::with(['customer', 'company', 'warehouse', 'paymentMethodDetails'])
            ->where('number', 'like', "%{$searchTerm}%")
            ->take(10)
            ->get();

        if ($models->isEmpty()) {
            return response()->json([
                'message' => "No {$this->modelName}s found.",
            ], 404);
        }

        return response()->json([
            'data' => $models,
            'message' => "{$this->modelName}s retrieved successfully."
        ], 200);
    }
}
