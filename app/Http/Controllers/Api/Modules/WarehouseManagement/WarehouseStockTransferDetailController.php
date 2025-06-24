<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseStockTransferDetail;
use App\Models\WarehouseStockTransfer;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseProductSerial;

class WarehouseStockTransferDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseStockTransferDetail::with([
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
            'warehouse_stock_transfer_id' => 'required|exists:warehouse_stock_transfers,id',
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
            $transfer = WarehouseStockTransferDetail::create([
                'warehouse_stock_transfer_id' => $validated['warehouse_stock_transfer_id'],
                'origin_warehouse_id' => $validated['origin_warehouse_id'],
                'origin_warehouse_product_id' => $validated['origin_warehouse_product_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'destination_warehouse_product_id' => $destinationProduct->id,
                'expected_qty' => $validated['quantity'],
                'transferred_qty' => 0,
                'remarks' => $validated['remarks'],
                'created_by_user_id' => Auth::id()
            ]);

            // Save serials if provided
            if (!empty($request->serials) && is_array($request->serials)) {
                foreach ($request->serials as $serial) {
                    \App\Models\WarehouseStockTransferSerial::create([
                        'warehouse_stock_transfer_id' => $validated['warehouse_stock_transfer_id'],
                        'warehouse_stock_transfer_detail_id' => $transfer->id,
                        'serial_number' => $serial['serial_number'] ?? null,
                        'batch_number' => $serial['batch_number'] ?? null,
                        'manufactured_at' => $serial['manufactured_at'] ?? null,
                        'expired_at' => $serial['expired_at'] ?? null,
                        'is_sold' => 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load([
                    'warehouseStockTransfer.originWarehouse',
                    'warehouseStockTransfer.originWarehouse.company',
                    'warehouseStockTransfer.destinationWarehouse',
                    'warehouseStockTransfer.destinationWarehouse.company',
                    'warehouseStockTransfer.createdByUser',
                    'warehouseStockTransfer.details'
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
            'destinationWarehouse.company',
            'createdByUser',
            'details'
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

    public function receive(Request $request, $detailId)
    {
        $detail = WarehouseStockTransferDetail::with(['warehouseStockTransfer', 'originWarehouseProduct', 'serials'])->findOrFail($detailId);
        $originProduct = $detail->originWarehouseProduct;
        $hasSerials = $originProduct->has_serials;

        $validated = $request->validate([
            'received_qty' => 'required|integer|min:1',
            'serials' => $hasSerials ? 'required|array' : 'nullable|array',
            'serials.*.serial_number' => $hasSerials ? 'required|string' : 'nullable|string',
        ]);

        // Validate transfer status
        if ($detail->warehouseStockTransfer->status !== 'approved') {
            return response()->json(['message' => 'Transfer must be approved before receiving items.'], 400);
        }

        // Validate quantity
        $remainingQty = $detail->expected_qty - $detail->transferred_qty;
        if ($validated['received_qty'] > $remainingQty) {
            return response()->json([
                'message' => 'Cannot receive more than the remaining quantity. Remaining: ' . $remainingQty
            ], 400);
        }

        // For serialized products, validate serials
        if ($hasSerials) {
            if (count($validated['serials']) !== $validated['received_qty']) {
                return response()->json([
                    'message' => 'Number of serials must match received quantity'
                ], 400);
            }

            // Get all expected serials for this detail
            $expectedSerials = $detail->serials()
                ->pluck('serial_number')
                ->toArray();

            // Validate each serial
            foreach ($validated['serials'] as $serial) {
                // Check if serial is part of the transfer
                if (!in_array($serial['serial_number'], $expectedSerials)) {
                    return response()->json([
                        'message' => "Serial number {$serial['serial_number']} is not part of this transfer"
                    ], 400);
                }

                // Check if serial exists in origin warehouse
                $exists = WarehouseProductSerial::where('warehouse_product_id', $originProduct->id)
                    ->where('serial_number', $serial['serial_number'])
                    ->exists();

                if (!$exists) {
                    return response()->json([
                        'message' => "Serial number {$serial['serial_number']} not found in origin warehouse"
                    ], 400);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Update transferred quantity
            $detail->transferred_qty += $validated['received_qty'];
            $detail->save();

            // Update transfer status
            $transfer = $detail->warehouseStockTransfer;
            
            // Check if all details are fully received
            $allDetailsComplete = $transfer->details()
                ->get()
                ->every(function($d) {
                    return $d->transferred_qty >= $d->expected_qty;
                });

            if ($allDetailsComplete) {
                $transfer->status = 'fully-transferred';
            } else {
                $transfer->status = 'partially-transferred';
            }
            $transfer->save();

            DB::commit();
            return response()->json([
                'message' => 'Items received successfully',
                'data' => $detail->fresh(['warehouseStockTransfer', 'serials'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to receive items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function return(Request $request, $detailId)
    {
        $detail = WarehouseStockTransferDetail::with(['warehouseStockTransfer', 'originWarehouseProduct', 'serials'])->findOrFail($detailId);
        $originProduct = $detail->originWarehouseProduct;
        $hasSerials = $originProduct->has_serials;

        $validated = $request->validate([
            'return_qty' => 'required|integer|min:1',
            'serials' => $hasSerials ? 'required|array' : 'nullable|array',
            'serials.*.serial_number' => $hasSerials ? 'required|string' : 'nullable|string',
        ]);

        // Validate transfer status
        if (!in_array($detail->warehouseStockTransfer->status, ['approved', 'partially-transferred'])) {
            return response()->json(['message' => 'Can only return items from approved or partially received transfers.'], 400);
        }

        // Validate return quantity
        if ($validated['return_qty'] > $detail->transferred_qty) {
            return response()->json([
                'message' => 'Cannot return more than the transferred quantity. Transferred: ' . $detail->transferred_qty
            ], 400);
        }

        // For serialized products, validate serials
        if ($hasSerials) {
            if (count($validated['serials']) !== $validated['return_qty']) {
                return response()->json([
                    'message' => 'Number of serials must match return quantity'
                ], 400);
            }

            // Get all serials for this detail
            $detailSerials = $detail->serials()
                ->pluck('serial_number')
                ->toArray();

            // Validate each serial
            foreach ($validated['serials'] as $serial) {
                if (!in_array($serial['serial_number'], $detailSerials)) {
                    return response()->json([
                        'message' => "Serial number {$serial['serial_number']} is not part of this transfer"
                    ], 400);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Update transferred quantity
            $detail->transferred_qty -= $validated['return_qty'];
            if ($detail->transferred_qty < 0) {
                $detail->transferred_qty = 0;
            }
            $detail->save();

            // Update transfer status
            $transfer = $detail->warehouseStockTransfer;
            
            // Check if any items are still transferred
            $anyTransferred = $transfer->details()
                ->where('transferred_qty', '>', 0)
                ->exists();

            if (!$anyTransferred) {
                $transfer->status = 'approved'; // Reset to approved if all items returned
            } else {
                $transfer->status = 'partially-transferred';
            }
            $transfer->save();

            DB::commit();
            return response()->json([
                'message' => 'Items returned successfully',
                'data' => $detail->fresh(['warehouseStockTransfer', 'serials'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to return items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
