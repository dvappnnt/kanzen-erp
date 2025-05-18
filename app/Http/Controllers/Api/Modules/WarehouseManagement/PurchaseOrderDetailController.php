<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Illuminate\Support\Facades\DB;

class PurchaseOrderDetailController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\PurchaseOrderDetail::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(PurchaseOrder $purchaseOrder)
    {
        $details = $purchaseOrder->details()
            ->with([
                'supplierProductDetail.product',
                'supplierProductDetail.variation.attributes.attribute',
                'supplierProductDetail.variation.attributes.attributeValue'
            ])
            ->get();

        return response()->json([
            'data' => $details
        ]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'supplier_product_detail_id' => 'required|exists:supplier_product_details,id',
            'qty' => 'required|numeric|min:0',
            'free_qty' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Create the purchase order detail with all validated fields
            $detail = new PurchaseOrderDetail();
            $detail->purchase_order_id = $purchaseOrder->id;
            $detail->supplier_product_detail_id = $validated['supplier_product_detail_id'];
            $detail->qty = $validated['qty'];
            $detail->free_qty = $validated['free_qty'] ?? 0;
            $detail->discount = $validated['discount'] ?? 0;
            $detail->price = $validated['price'];
            $detail->total = $validated['total'];
            $detail->notes = $validated['notes'] ?? null;
            $detail->save();

            // Update purchase order totals
            $this->updatePurchaseOrderTotals($purchaseOrder);

            DB::commit();

        return response()->json([
                'message' => 'Purchase order detail created successfully',
                'data' => $detail
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create purchase order detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company', 'warehouse', 'supplier'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderDetail $detail)
    {
        $validated = $request->validate([
            'supplier_product_detail_id' => 'required|exists:supplier_product_details,id',
            'qty' => 'required|numeric|min:0',
            'free_qty' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $detail->supplier_product_detail_id = $validated['supplier_product_detail_id'];
            $detail->qty = $validated['qty'];
            $detail->free_qty = $validated['free_qty'] ?? 0;
            $detail->discount = $validated['discount'] ?? 0;
            $detail->price = $validated['price'];
            $detail->total = $validated['total'];
            $detail->notes = $validated['notes'] ?? null;
            $detail->save();
            
            // Update purchase order totals
            $this->updatePurchaseOrderTotals($purchaseOrder);

            DB::commit();

        return response()->json([
                'message' => 'Purchase order detail updated successfully',
                'data' => $detail
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update purchase order detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderDetail $detail)
    {
        DB::beginTransaction();
        try {
            $detail->delete();
            
            // Update purchase order totals
            $this->updatePurchaseOrderTotals($purchaseOrder);

            DB::commit();

            return response()->json([
                'message' => 'Purchase order detail deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete purchase order detail',
                'error' => $e->getMessage()
            ], 500);
        }
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

    protected function updatePurchaseOrderTotals(PurchaseOrder $purchaseOrder)
    {
        $totals = $purchaseOrder->details()
            ->selectRaw('
                SUM(total) as subtotal,
                SUM(discount) as total_discount,
                SUM(qty) as total_qty,
                SUM(free_qty) as total_free_qty
            ')
            ->first();

        $purchaseOrder->update([
            'subtotal' => $totals->subtotal ?? 0,
            'total_discount' => $totals->total_discount ?? 0,
            'total_qty' => $totals->total_qty ?? 0,
            'total_free_qty' => $totals->total_free_qty ?? 0,
            'total' => ($totals->subtotal ?? 0) - ($totals->total_discount ?? 0)
        ]);
    }
}
