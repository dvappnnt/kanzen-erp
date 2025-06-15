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
            'originWarehouse.company',
            'destinationWarehouse',
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
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
            'transfer_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            // Create the transfer record
            $transfer = WarehouseStockTransfer::create([
                'origin_warehouse_id' => $validated['origin_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'created_by_user_id' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load([
                    'originWarehouse',
                    'destinationWarehouse',
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
            'originWarehouse.company',
            'destinationWarehouse',
            'createdByUser',
            'details',
            'details.serials'
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