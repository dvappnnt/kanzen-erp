<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseStockTransfer;
use App\Models\WarehouseStockTransferDetail;
use App\Models\WarehouseStockTransferSerial;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\WarehouseProductSerial;
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
            'details' => 'required|array',
            'details.*.origin_warehouse_product_id' => 'required|exists:warehouse_products,id',
            'details.*.expected_qty' => 'required|integer|min:1',
            'details.*.serials' => 'array',
            'details.*.serials.*.serial_number' => 'required_if:details.*.has_serials,true|string',
            'details.*.serials.*.batch_number' => 'nullable|string',
            'details.*.serials.*.manufactured_at' => 'nullable|date',
            'details.*.serials.*.expired_at' => 'nullable|date|after:manufactured_at',
        ]);

        try {
            DB::beginTransaction();

            // Create the transfer record
            $transfer = WarehouseStockTransfer::create([
                'origin_warehouse_id' => $validated['origin_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'status' => 'pending',
                'created_by_user_id' => Auth::id()
            ]);

            // Process each detail
            foreach ($validated['details'] as $detail) {
                $originProduct = WarehouseProduct::findOrFail($detail['origin_warehouse_product_id']);
                
                // Create or get destination warehouse product
                $destinationProduct = WarehouseProduct::firstOrCreate(
                    [
                        'warehouse_id' => $validated['destination_warehouse_id'],
                        'supplier_product_detail_id' => $originProduct->supplier_product_detail_id,
                    ],
                    [
                        'sku' => $originProduct->sku,
                        'barcode' => $originProduct->barcode,
                        'critical_level_qty' => $originProduct->critical_level_qty,
                        'qty' => 0,
                        'price' => $originProduct->price,
                        'last_cost' => $originProduct->last_cost,
                        'average_cost' => $originProduct->average_cost,
                        'has_serials' => $originProduct->has_serials,
                    ]
                );

                // Create transfer detail
                $transferDetail = WarehouseStockTransferDetail::create([
                    'warehouse_stock_transfer_id' => $transfer->id,
                    'origin_warehouse_product_id' => $originProduct->id,
                    'destination_warehouse_product_id' => $destinationProduct->id,
                    'expected_qty' => $detail['expected_qty'],
                    'transferred_qty' => 0,
                ]);

                // Process serials if any
                if (!empty($detail['serials'])) {
                    foreach ($detail['serials'] as $serial) {
                        WarehouseStockTransferSerial::create([
                            'warehouse_stock_transfer_id' => $transfer->id,
                            'warehouse_stock_transfer_detail_id' => $transferDetail->id,
                            'serial_number' => $serial['serial_number'],
                            'batch_number' => $serial['batch_number'] ?? null,
                            'manufactured_at' => $serial['manufactured_at'] ?? null,
                            'expired_at' => $serial['expired_at'] ?? null,
                            'is_sold' => false,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load([
                    'originWarehouse',
                    'destinationWarehouse',
                    'createdByUser',
                    'details',
                    'details.serials'
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

    public function validateSerial(Request $request)
    {
        $request->validate([
            'warehouse_stock_transfer_id' => 'required|exists:warehouse_stock_transfers,id',
            'warehouse_product_id' => 'required|exists:warehouse_products,id',
            'serial_number' => 'required|string',
        ]);

        try {
            // Get the transfer and check its status
            $transfer = WarehouseStockTransfer::findOrFail($request->warehouse_stock_transfer_id);
            
            if (!in_array($transfer->status, ['approved', 'partially_received'])) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Transfer must be approved before receiving items'
                ], 400);
            }

            // First check if the serial exists in the origin warehouse
            $serial = WarehouseProductSerial::where('warehouse_product_id', $request->warehouse_product_id)
                ->where('serial_number', $request->serial_number)
                ->first();

            if (!$serial) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Serial number not found in origin warehouse'
                ], 404);
            }

            if ($serial->is_sold) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Serial number is already sold'
                ], 400);
            }

            // Check if this serial is part of the transfer
            $transferSerial = WarehouseStockTransferSerial::where('warehouse_stock_transfer_id', $request->warehouse_stock_transfer_id)
                ->where('serial_number', $request->serial_number)
                ->first();

            if (!$transferSerial) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Serial number is not part of this transfer'
                ], 400);
            }

            // Check if serial is already in another active transfer
            $existingTransfer = WarehouseStockTransferSerial::whereHas('transfer', function($q) use ($request) {
                $q->whereIn('status', ['pending', 'approved', 'partially_received'])
                  ->where('id', '!=', $request->warehouse_stock_transfer_id);
            })->where('serial_number', $request->serial_number)
              ->first();

            if ($existingTransfer) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Serial number is already in another active transfer'
                ], 400);
            }

            return response()->json([
                'valid' => true,
                'data' => $serial
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error validating serial: ' . $e->getMessage()
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

    public function approve($id)
    {
        $transfer = WarehouseStockTransfer::findOrFail($id);
        
        if ($transfer->status !== 'pending') {
            return response()->json([
                'message' => 'Stock transfer cannot be approved in its current state'
            ], 400);
        }

        $transfer->update([
            'status' => 'approved',
            'remarks' => request('notes')
        ]);

        return response()->json([
            'message' => 'Stock transfer approved successfully',
            'data' => $transfer
        ]);
    }

    public function reject($id)
    {
        $transfer = WarehouseStockTransfer::findOrFail($id);
        
        if ($transfer->status !== 'pending') {
            return response()->json([
                'message' => 'Stock transfer cannot be rejected in its current state'
            ], 400);
        }

        $transfer->update([
            'status' => 'rejected',
            'remarks' => request('notes')
        ]);

        return response()->json([
            'message' => 'Stock transfer rejected successfully',
            'data' => $transfer
        ]);
    }

    public function cancel($id)
    {
        $transfer = WarehouseStockTransfer::findOrFail($id);
        
        if ($transfer->status !== 'pending') {
            return response()->json([
                'message' => 'Stock transfer cannot be cancelled in its current state'
            ], 400);
        }

        $transfer->update([
            'status' => 'cancelled',
            'remarks' => request('notes')
        ]);

        return response()->json([
            'message' => 'Stock transfer cancelled successfully',
            'data' => $transfer
        ]);
    }

    public function complete($id)
    {
        $transfer = WarehouseStockTransfer::with(['details.originWarehouseProduct', 'details.destinationWarehouseProduct', 'details.serials'])
            ->findOrFail($id);
        
        if ($transfer->status !== 'fully-transferred') {
            return response()->json([
                'message' => 'Transfer must be fully transferred before completing'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Process each detail
            foreach ($transfer->details as $detail) {
                // Deduct from origin warehouse
                $originProduct = $detail->originWarehouseProduct;
                $originProduct->decrement('qty', $detail->transferred_qty);

                // Add to destination warehouse
                $destinationProduct = $detail->destinationWarehouseProduct;
                $destinationProduct->increment('qty', $detail->transferred_qty);

                // Update serial numbers if any
                if ($originProduct->has_serials) {
                    foreach ($detail->serials as $serial) {
                        // Move serial to destination warehouse
                        WarehouseProductSerial::where('warehouse_product_id', $originProduct->id)
                            ->where('serial_number', $serial->serial_number)
                            ->update([
                                'warehouse_product_id' => $destinationProduct->id
                            ]);
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'completed'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Stock transfer completed successfully',
                'data' => $transfer->fresh()->load([
                    'originWarehouse',
                    'destinationWarehouse',
                    'details',
                    'details.serials'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to complete stock transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}