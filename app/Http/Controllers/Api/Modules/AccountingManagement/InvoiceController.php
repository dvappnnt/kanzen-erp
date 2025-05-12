<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            'payment_method' => 'required|string|in:cash,bank-transfer,credit-card,gcash,other',
            'payment_details' => 'required|array',
            'payment_details.account_number' => 'required_if:payment_method,bank-transfer,credit-card,gcash',
            'payment_details.account_name' => 'required_if:payment_method,bank-transfer,credit-card',
            'payment_details.bank_id' => 'required_if:payment_method,bank-transfer',
            'payment_details.company_account_id' => 'required_if:payment_method,bank-transfer',
            'tax_rate' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
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
                'payment_date' => now(),
                'discount_rate' => $validated['discount_rate'],
                'discount_amount' => $validated['discount_amount'],
                'tax_rate' => $validated['tax_rate'],
                'tax_amount' => $validated['tax_amount'],
                'shipping_cost' => $validated['shipping_cost'],
                'subtotal' => $validated['subtotal'],
                'total_amount' => $validated['total_amount'],
                'currency' => 'PHP',
                'status' => 'fully-paid',
                'created_by_user_id' => Auth::id()
            ]);

            // Create payment method details
            $invoice->paymentMethodDetails()->create([
                'payment_method' => $validated['payment_method'],
                'account_number' => $validated['payment_details']['account_number'] ?? null,
                'account_name' => $validated['payment_details']['account_name'] ?? null,
                'reference_number' => $validated['payment_details']['reference_number'] ?? null,
                'company_account_id' => $validated['payment_details']['company_account_id'] ?? null,
                'status' => 'fully-paid',
                'payment_date' => now(),
                'amount' => $validated['total_amount']
            ]);

            // Create invoice details and handle serials
            foreach ($validated['items'] as $item) {
                Log::info('Processing item:', $item);
                
                $detail = $invoice->details()->create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'warehouse_product_id' => $item['warehouse_product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'currency' => 'PHP'
                ]);

                Log::info('Created invoice detail:', ['detail_id' => $detail->id]);

                // Deduct quantity from warehouse_product
                $warehouseProduct = WarehouseProduct::find($item['warehouse_product_id']);
                if ($warehouseProduct) {
                    $warehouseProduct->decrement('qty', $item['qty']);
                    Log::info('Updated warehouse product quantity:', [
                        'product_id' => $warehouseProduct->id,
                        'old_qty' => $warehouseProduct->qty + $item['qty'],
                        'new_qty' => $warehouseProduct->qty
                    ]);
                }

                // Handle serials if present
                if (isset($item['serials']) && !empty($item['serials'])) {
                    Log::info('Processing serials for item:', [
                        'item_id' => $item['warehouse_product_id'],
                        'serials' => $item['serials']
                    ]);

                    foreach ($item['serials'] as $serialNumber) {
                        Log::info('Looking up serial:', ['serial_number' => $serialNumber]);
                        
                        $serial = WarehouseProductSerial::where('serial_number', $serialNumber)
                            ->where('warehouse_product_id', $item['warehouse_product_id'])
                            ->where('is_sold', 0)
                            ->first();

                        Log::info('Found serial:', ['serial' => $serial]);

                        if ($serial) {
                            try {
                                $invoiceSerial = new InvoiceSerial([
                                    'warehouse_product_id' => $item['warehouse_product_id'],
                                    'warehouse_product_serial_id' => $serial->id,
                                    'is_expired' => false,
                                    'is_replaced' => false
                                ]);
                                
                                $detail->invoiceSerials()->save($invoiceSerial);
                                
                                Log::info('Created invoice serial:', [
                                    'invoice_serial_id' => $invoiceSerial->id,
                                    'detail_id' => $detail->id
                                ]);

                                $serial->update(['is_sold' => 1]);
                            } catch (\Exception $e) {
                                Log::error('Error creating invoice serial:', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                throw $e;
                            }
                        } else {
                            throw new \Exception("Serial number '{$serialNumber}' not found or already sold for this product.");
                        }
                    }
                }
            }

            DB::commit();

            // Load relationships for the response
            $invoice->load(['details.invoiceSerials', 'details.warehouseProduct.supplierProductDetail.product', 'customer', 'company', 'paymentMethodDetails']);

            return response()->json([
                'data' => $invoice,
                'message' => "Invoice '{$invoice->number}' created successfully."
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
