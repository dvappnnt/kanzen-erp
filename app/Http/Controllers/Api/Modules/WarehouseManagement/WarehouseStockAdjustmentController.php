<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseStockAdjustment;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WarehouseStockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseStockAdjustment::with(['warehouse', 'warehouseProduct.supplierProductDetail.product', 'adjustedByUser'])
            ->latest();

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('warehouse_product_id')) {
            $query->where('warehouse_product_id', $request->warehouse_product_id);
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_product_id' => 'required|exists:warehouse_products,id',
            'system_quantity' => 'required|integer|min:0',
            'actual_quantity' => 'required|integer|min:0',
            'adjustment' => 'required|integer',
            'reason' => 'required|in:damaged,lost,count-correction,other',
            'remarks' => 'nullable|string|max:1024',
        ]);

        try {
            DB::beginTransaction();

            // Create the stock adjustment record
            $adjustment = WarehouseStockAdjustment::create([
                ...$validated,
                'adjusted_at' => now(),
                'adjusted_by_user_id' => Auth::id(),
            ]);

            // Update the warehouse product quantity
            $warehouseProduct = WarehouseProduct::findOrFail($request->warehouse_product_id);
            $warehouseProduct->qty = $request->actual_quantity;
            $warehouseProduct->save();

            DB::commit();

            return response()->json([
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment->load(['warehouse', 'warehouseProduct.supplierProductDetail.product', 'adjustedByUser'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $adjustment = WarehouseStockAdjustment::with(['warehouse', 'warehouseProduct.supplierProductDetail.product', 'adjustedByUser'])
            ->findOrFail($id);
        
        return response()->json($adjustment);
    }

    public function destroy($id)
    {
        $adjustment = WarehouseStockAdjustment::findOrFail($id);
        $adjustment->delete();

        return response()->json(['message' => 'Stock adjustment deleted successfully']);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $query = WarehouseStockAdjustment::with(['warehouse', 'warehouseProduct.supplierProductDetail.product', 'adjustedByUser'])
            ->where(function($q) use ($request) {
                $q->whereHas('warehouse', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                })
                ->orWhereHas('warehouseProduct', function($q) use ($request) {
                    $q->whereHas('supplierProductDetail.product', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    });
                });
            })
            ->take(10)
            ->get();

        return response()->json([
            'data' => $query,
            'message' => 'Stock adjustments retrieved successfully'
        ]);
    }
}