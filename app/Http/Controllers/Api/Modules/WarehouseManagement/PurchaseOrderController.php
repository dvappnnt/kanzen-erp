<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;

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
            'shipping_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
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
            'product_variation_id' => 'required|exists:product_variations,id',
            'attribute_id' => 'required|exists:attributes,id',
            'attribute_value_id' => 'required|exists:attribute_values,id',
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

        $models = $this->modelClass::with(['company', 'warehouse', ''])
            ->where('name', 'like', "%{$searchTerm}%")
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

            // Update purchase order status
            $purchaseOrder->update(['status' => 'ordered']);
            
            DB::commit();

            return response()->json([
                'message' => 'Purchase order marked as ordered and goods receipt created successfully',
                'data' => [
                    'purchase_order' => $purchaseOrder,
                    'goods_receipt_id' => $goodsReceipt->id
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
            $purchaseOrder->update(['status' => 'approved']);
            
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
}