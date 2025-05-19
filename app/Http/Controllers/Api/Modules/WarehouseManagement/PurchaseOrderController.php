<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceDetail;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\PurchaseOrder::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['company', 'warehouse', 'supplier'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:255',
            'custom_payment_value' => 'nullable|integer|min:1',
            'custom_payment_unit' => 'nullable|in:days,months',
            'shipping_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        // Format custom payment terms
        if ($validated['payment_terms'] === 'custom' && $validated['custom_payment_value'] && $validated['custom_payment_unit']) {
            $validated['payment_terms'] = sprintf('custom_%d_%s', 
                $validated['custom_payment_value'], 
                $validated['custom_payment_unit']
            );
        }

        // Set default values for nullable fields
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['shipping_cost'] = $validated['shipping_cost'] ?? 0;
        $validated['subtotal'] = $validated['subtotal'] ?? 0;
        $validated['total_amount'] = $validated['total_amount'] ?? 0;

        // Remove custom payment fields as they're not in the table
        unset($validated['custom_payment_value']);
        unset($validated['custom_payment_unit']);

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company', 'warehouse', 'supplier'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:255',
            'shipping_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        // Set default values for nullable fields
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['shipping_cost'] = $validated['shipping_cost'] ?? 0;
        $validated['subtotal'] = $validated['subtotal'] ?? 0;
        $validated['total_amount'] = $validated['total_amount'] ?? 0;

        $model->update($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} updated successfully.",
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

        $models = $this->modelClass::with(['company', 'warehouse'])
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

    public function pending(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->update(['status' => 'pending']);
            
            return response()->json([
                'message' => 'Purchase order marked as pending successfully',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark purchase order as pending',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->update(['status' => 'cancelled']);
            
            return response()->json([
                'message' => 'Purchase order cancelled successfully',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function order(Request $request, PurchaseOrder $purchaseOrder)
    {
        DB::beginTransaction();
        try {
            // First create the goods receipt
            $goodsReceipt = GoodsReceipt::create([
                'company_id' => $purchaseOrder->company_id,
                'purchase_order_id' => $purchaseOrder->id,
                'date' => now(),
                'notes' => "Auto-generated from PO: {$purchaseOrder->number}",
                'created_by_user_id' => $request->user()->id
            ]);

            // Create goods receipt details for each purchase order detail
            foreach ($purchaseOrder->details as $poDetail) {
                GoodsReceiptDetail::create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_detail_id' => $poDetail->id,
                    'expected_qty' => $poDetail->qty,
                    'received_qty' => 0,
                    'notes' => null
                ]);
            }

            // Calculate due date based on payment terms
            $dueDate = $this->calculateDueDate(
                $purchaseOrder->payment_terms,
                null, // customValue is now encoded in payment_terms
                null, // customUnit is now encoded in payment_terms
                now(),
                $purchaseOrder->expected_delivery_date
            );

            // Create supplier invoice
            $supplierInvoice = SupplierInvoice::create([
                'invoice_number' => 'SUP-INV-' . str_pad(SupplierInvoice::count() + 1, 6, '0', STR_PAD_LEFT),
                'company_id' => $purchaseOrder->company_id,
                'goods_receipt_id' => $goodsReceipt->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->id,
                'invoice_date' => now(),
                'due_date' => $dueDate,
                'tax_rate' => $purchaseOrder->tax_rate ?? 0,
                'tax_amount' => $purchaseOrder->tax_amount ?? 0,
                'shipping_cost' => $purchaseOrder->shipping_cost ?? 0,
                'subtotal' => $purchaseOrder->subtotal ?? 0,
                'total_amount' => $purchaseOrder->total_amount ?? 0,
                'status' => 'unpaid',
                'remarks' => "Auto-generated from PO: {$purchaseOrder->number}",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create supplier invoice details
            foreach ($purchaseOrder->details as $poDetail) {
                // Get the supplier product detail
                $supplierProductDetail = \App\Models\SupplierProductDetail::findOrFail($poDetail->supplier_product_detail_id);
                
                // Get the corresponding supplier product
                $supplierProduct = \App\Models\SupplierProduct::where('supplier_id', $purchaseOrder->supplier_id)
                    ->where('product_id', $supplierProductDetail->product_id)
                    ->firstOrFail();
                
                SupplierInvoiceDetail::create([
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'supplier_product_id' => $supplierProduct->id,
                    'quantity' => $poDetail->qty,
                    'unit_price' => $poDetail->price,
                    'total' => $poDetail->total,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Update purchase order status
            $purchaseOrder->update(['status' => 'ordered']);
            
            DB::commit();

            return response()->json([
                'message' => 'Purchase order marked as ordered, goods receipt and supplier invoice created successfully',
                'data' => [
                    'purchase_order' => $purchaseOrder,
                    'goods_receipt_id' => $goodsReceipt->id,
                    'supplier_invoice_id' => $supplierInvoice->id
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->update(['status' => 'received']);
            
            return response()->json([
                'message' => 'Purchase order marked as received successfully',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark purchase order as received',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->update(['status' => 'partially-approved']);
            
            return response()->json([
                'message' => 'Purchase order approved successfully',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->update(['status' => 'rejected']);
            
            return response()->json([
                'message' => 'Purchase order rejected successfully',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add this helper method to calculate due date based on payment terms
    private function calculateDueDate($paymentTerms, $customValue = null, $customUnit = null, $baseDate = null, $expectedDeliveryDate = null)
    {
        $baseDate = $baseDate ?? now();

        // Parse custom payment terms if stored in the format "custom_X_unit"
        if (strpos($paymentTerms, 'custom_') === 0) {
            $parts = explode('_', $paymentTerms);
            if (count($parts) === 3) {
                $customValue = (int)$parts[1];
                $customUnit = $parts[2];
            }
        }
        
        switch ($paymentTerms) {
            case 'immediate':
                return $baseDate; // Due immediately (today)
            case 'net_15':
                return $baseDate->copy()->addDays(15);
            case 'net_30':
                return $baseDate->copy()->addDays(30);
            case 'net_45':
                return $baseDate->copy()->addDays(45);
            case 'net_60':
                return $baseDate->copy()->addDays(60);
            case 'eom':
                return $baseDate->copy()->endOfMonth();
            case 'cod':
                // If COD and expected delivery date is set, use that, otherwise null
                return $expectedDeliveryDate ? Carbon::parse($expectedDeliveryDate) : null;
            default:
                // Handle custom payment terms
                if (strpos($paymentTerms, 'custom_') === 0 && $customValue) {
                    return $customUnit === 'days' 
                        ? $baseDate->copy()->addDays($customValue)
                        : $baseDate->copy()->addMonths($customValue);
                }
                return null;
        }
    }
}