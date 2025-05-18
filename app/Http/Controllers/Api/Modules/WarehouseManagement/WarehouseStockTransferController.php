<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseStockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseStockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseStockTransfer::with([
            'originWarehouse',
            'originWarehouseProduct.supplierProductDetail.product',
            'destinationWarehouse',
            'destinationWarehouseProduct.supplierProductDetail.product',
            'createdByUser'
        ])->latest();

        if ($request->has('warehouse_id')) {
            $query->where(function($q) use ($request) {
                $q->where('origin_warehouse_id', $request->warehouse_id)
                  ->orWhere('destination_warehouse_id', $request->warehouse_id);
            });
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origin_warehouse_id' => 'required|exists:warehouses,id',
            'origin_warehouse_product_id' => 'required|exists:warehouse_products,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1024',
        ]);

        try {
            DB::beginTransaction();

            // Get the origin warehouse product
            $originProduct = WarehouseProduct::findOrFail($validated['origin_warehouse_product_id']);

            // Check if there's enough stock
            if ($originProduct->qty < $validated['quantity']) {
                throw new \Exception('Insufficient stock in origin warehouse');
            }

            // Find or create the destination warehouse product
            $destinationProduct = WarehouseProduct::firstOrCreate(
                [
                    'warehouse_id' => $validated['destination_warehouse_id'],
                    'supplier_product_detail_id' => $originProduct->supplier_product_detail_id,
                ],
                [
                    'qty' => 0,
                    'price' => $originProduct->price,
                    'last_cost' => $originProduct->last_cost,
                    'average_cost' => $originProduct->average_cost,
                    'has_serials' => $originProduct->has_serials,
                    'critical_level_qty' => $originProduct->critical_level_qty
                ]
            );

            // Create the transfer record
            $transfer = WarehouseStockTransfer::create([
                'origin_warehouse_id' => $validated['origin_warehouse_id'],
                'origin_warehouse_product_id' => $validated['origin_warehouse_product_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'destination_warehouse_product_id' => $destinationProduct->id,
                'quantity' => $validated['quantity'],
                'remarks' => $validated['remarks'],
                'created_by_user_id' => Auth::id()
            ]);

            // Update the stock quantities
            $originProduct->decrement('qty', $validated['quantity']);
            $destinationProduct->increment('qty', $validated['quantity']);

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load([
                    'originWarehouse',
                    'originWarehouseProduct.supplierProductDetail.product',
                    'destinationWarehouse',
                    'destinationWarehouseProduct.supplierProductDetail.product',
                    'createdByUser'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create stock transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $transfer = WarehouseStockTransfer::with([
            'originWarehouse',
            'originWarehouseProduct.supplierProductDetail.product',
            'destinationWarehouse',
            'destinationWarehouseProduct.supplierProductDetail.product',
            'createdByUser'
        ])->findOrFail($id);
        
        return response()->json($transfer);
    }

    public function destroy($id)
    {
        $transfer = WarehouseStockTransfer::findOrFail($id);
        $transfer->delete();

        return response()->json(['message' => 'Stock transfer deleted successfully']);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $query = WarehouseStockTransfer::with([
            'originWarehouse',
            'originWarehouseProduct.supplierProductDetail.product',
            'destinationWarehouse',
            'destinationWarehouseProduct.supplierProductDetail.product',
            'createdByUser'
        ])
        ->where(function($q) use ($request) {
            $q->whereHas('originWarehouse', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->orWhereHas('destinationWarehouse', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->orWhereHas('originWarehouseProduct.supplierProductDetail.product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        })
        ->take(10)
        ->get();

        return response()->json([
            'data' => $query,
            'message' => 'Stock transfers retrieved successfully'
        ]);
    }
}