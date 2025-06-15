<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseProduct;
use App\Models\WarehouseProductSerial;
use App\Models\GoodsReceiptDetail;
use App\Models\GoodsReceiptSerial;
use Illuminate\Support\Facades\DB;

class GoodsReceiptDetailController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\GoodsReceiptDetail::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with([
            'goodsReceipt',
            'purchaseOrderDetail',
            'purchaseOrderDetail.supplierProductDetail',
            'purchaseOrderDetail.supplierProductDetail.product',
            'purchaseOrderDetail.supplierProductDetail.variation',
            'serials'
        ]);

        if ($request->has('goods_receipt_id')) {
            $query->where('goods_receipt_id', $request->goods_receipt_id);
        }

        return response()->json($query->latest()->get());
    }

    public function receive(Request $request, GoodsReceiptDetail $goodsReceiptDetail)
    {
        $request->validate([
            'received_qty' => 'required|numeric|min:0',
            'has_serials' => 'required|boolean',
            'type' => 'required_if:has_serials,true|in:serial_numbers,batch_numbers',
            'serials' => 'required_if:has_serials,true|array',
            'serials.*.serial_number' => 'required_if:type,serial_numbers|string|nullable',
            'serials.*.batch_number' => 'required_if:type,batch_numbers|string|nullable',
            'serials.*.manufactured_at' => 'nullable|date',
            'serials.*.expired_at' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // For items with serials, we'll set the received quantity based on successful serial entries
            if (!$request->has_serials) {
                $goodsReceiptDetail->received_qty += $request->received_qty;
            }

            if ($goodsReceiptDetail->received_qty > $goodsReceiptDetail->expected_qty) {
                throw new \Exception('Received quantity cannot exceed expected quantity');
            }

            // Set has_serials to 1 if we're receiving with serials
            if ($request->has_serials) {
                $goodsReceiptDetail->has_serials = true;
            }

            $goodsReceiptDetail->save();

            // Create serial/batch records if needed
            $errors = [];
            $success = [];
            $successfulQty = 0;

            if ($request->has_serials && !empty($request->serials)) {
                foreach ($request->serials as $index => $serial) {
                    try {
                        // Additional validation for each serial
                        if (!empty($serial['manufactured_at']) && !empty($serial['expired_at'])) {
                            $manufacturedDate = new \DateTime($serial['manufactured_at']);
                            $expiryDate = new \DateTime($serial['expired_at']);

                            if ($expiryDate <= $manufacturedDate) {
                                throw new \Exception('Expiry date must be after manufactured date');
                            }
                        }

                        // Check for duplicate serial numbers if using serial numbers
                        if ($request->type === 'serial_numbers' && !empty($serial['serial_number'])) {
                            $exists = GoodsReceiptSerial::where('serial_number', $serial['serial_number'])
                                ->where('goods_receipt_detail_id', '!=', $goodsReceiptDetail->id)
                                ->exists();

                            if ($exists) {
                                throw new \Exception('Serial number already exists');
                            }
                        }

                        $serialRecord = GoodsReceiptSerial::create([
                            'goods_receipt_detail_id' => $goodsReceiptDetail->id,
                            'serial_number' => $request->type === 'serial_numbers' ? $serial['serial_number'] : null,
                            'batch_number' => $request->type === 'batch_numbers' ? $serial['batch_number'] : null,
                            'manufactured_at' => $serial['manufactured_at'],
                            'expired_at' => $serial['expired_at'],
                        ]);

                        $success[] = [
                            'index' => $index,
                            'data' => $serialRecord
                        ];
                        $successfulQty++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $index,
                            'message' => $e->getMessage()
                        ];
                    }
                }

                // Update received quantity to match successful serials
                if ($request->has_serials) {
                    // Get current count of serials
                    $currentSerialCount = $goodsReceiptDetail->serials()->count();

                    // Set received_qty to match total number of serials
                    $goodsReceiptDetail->received_qty = $currentSerialCount;

                    if ($goodsReceiptDetail->received_qty > $goodsReceiptDetail->expected_qty) {
                        throw new \Exception('Total serial numbers cannot exceed expected quantity');
                    }

                    $goodsReceiptDetail->save();
                }
            }

            // Update GoodsReceipt status
            $goodsReceipt = $goodsReceiptDetail->goodsReceipt;
            if ($goodsReceipt->status !== 'in-warehouse') {
                $allDetails = $goodsReceipt->details()->get();
                $totalExpected = $allDetails->sum('expected_qty');
                $totalReceived = $allDetails->sum('received_qty');

                if ($totalReceived === 0) {
                    $goodsReceipt->status = 'pending';
                } elseif ($totalReceived < $totalExpected) {
                    $goodsReceipt->status = 'partially-received';
                } elseif ($totalReceived === $totalExpected) {
                    $goodsReceipt->status = 'fully-received';
                }
                $goodsReceipt->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Items processed',
                'data' => $goodsReceiptDetail->load('serials'),
                'results' => [
                    'success' => $success,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function return(Request $request, GoodsReceiptDetail $goodsReceiptDetail)
    {
        // Check if the item has serials
        if ($goodsReceiptDetail->has_serials) {
            return response()->json([
                'message' => 'Items with serial/batch numbers cannot be returned. Please delete the serial/batch numbers first.'
            ], 422);
        }

        $request->validate([
            'return_qty' => 'required|numeric|min:1|max:' . $goodsReceiptDetail->received_qty,
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Update received quantity
            $goodsReceiptDetail->received_qty -= $request->return_qty;
            $goodsReceiptDetail->notes = $request->notes;
            $goodsReceiptDetail->save();

            // Update GoodsReceipt status
            $goodsReceipt = $goodsReceiptDetail->goodsReceipt;
            if ($goodsReceipt->status !== 'in-warehouse') {
                $allDetails = $goodsReceipt->details()->get();
                $totalExpected = $allDetails->sum('expected_qty');
                $totalReceived = $allDetails->sum('received_qty');

                if ($totalReceived === 0) {
                    $goodsReceipt->status = 'pending';
                } elseif ($totalReceived < $totalExpected) {
                    $goodsReceipt->status = 'partially-received';
                } elseif ($totalReceived === $totalExpected) {
                    $goodsReceipt->status = 'fully-received';
                }
                $goodsReceipt->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Items returned successfully',
                'data' => $goodsReceiptDetail->load('serials')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_variation_id' => 'required|exists:product_variations,id',
            'attribute_id' => 'required|exists:attributes,id',
            'attribute_value_id' => 'required|exists:attribute_values,id',
        ]);

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
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

        $models = $this->modelClass::with(['company'])
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

    public function deleteSerial($serialId)
    {
        try {
            DB::beginTransaction();

            $serial = GoodsReceiptSerial::findOrFail($serialId);
            $goodsReceiptDetail = $serial->goodsReceiptDetail;

            // Delete the serial
            $serial->delete();

            // Update the received quantity
            $goodsReceiptDetail->received_qty -= 1;
            $goodsReceiptDetail->save();

            DB::commit();

            return response()->json([
                'message' => 'Serial/batch number deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateSerial(Request $request, $serialId)
    {
        try {
            DB::beginTransaction();

            $serial = GoodsReceiptSerial::findOrFail($serialId);

            $request->validate([
                'serial_number' => 'nullable|string',
                'batch_number' => 'nullable|string',
                'manufactured_at' => 'nullable|date',
                'expired_at' => 'nullable|date',
                'notes' => 'nullable|string'
            ]);

            // Additional validation for dates
            if ($request->filled('manufactured_at') && $request->filled('expired_at')) {
                $manufacturedDate = new \DateTime($request->manufactured_at);
                $expiryDate = new \DateTime($request->expired_at);

                if ($expiryDate <= $manufacturedDate) {
                    throw new \Exception('Expiry date must be after manufactured date');
                }
            }

            // Check for duplicate serial numbers
            if ($request->filled('serial_number')) {
                $exists = GoodsReceiptSerial::where('serial_number', $request->serial_number)
                    ->where('id', '!=', $serialId)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Serial number already exists');
                }
            }

            $serial->update($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Serial/batch number updated successfully',
                'data' => $serial->goodsReceiptDetail->load('serials')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function sync(Request $request, GoodsReceiptDetail $goodsReceiptDetail)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'received_qty' => 'required|numeric|min:0',
            ]);

            $goodsReceiptDetail->is_synced = true;
            $goodsReceiptDetail->save();

            // Find or create warehouse product
            $warehouseProduct = WarehouseProduct::firstOrCreate(
                [
                    'warehouse_id' => $goodsReceiptDetail->goodsReceipt->purchaseOrder->warehouse_id,
                    'supplier_product_detail_id' => $goodsReceiptDetail->purchaseOrderDetail->supplier_product_detail_id,
                ],
                [
                    'qty' => 0,
                    'has_serials' => count($goodsReceiptDetail->serials) > 0,
                    'price' => $goodsReceiptDetail->purchaseOrderDetail->price,
                    'last_cost' => $goodsReceiptDetail->purchaseOrderDetail->price,
                    'sku' => $goodsReceiptDetail->purchaseOrderDetail->supplierProductDetail->productVariation->sku,
                    'barcode' => $goodsReceiptDetail->purchaseOrderDetail->supplierProductDetail->productVariation->barcode
                ]
            );

            // Update quantity
            $warehouseProduct->qty += $goodsReceiptDetail->received_qty;
            $warehouseProduct->save();

            // Transfer serials if any
            if ($goodsReceiptDetail->serials->count() > 0) {
                foreach ($goodsReceiptDetail->serials as $serial) {
                    WarehouseProductSerial::create([
                        'warehouse_product_id' => $warehouseProduct->id,
                        'serial_number' => $serial->serial_number,
                        'batch_number' => $serial->batch_number,
                        'manufactured_at' => $serial->manufactured_at,
                        'expired_at' => $serial->expired_at
                    ]);
                }
            }

            // Check if all details are synced
            $goodsReceipt = $goodsReceiptDetail->goodsReceipt;
            $allDetailsSynced = $goodsReceipt->details()
                ->where('is_synced', false)
                ->count() === 0;

            // If all details are synced, update goods receipt status
            if ($allDetailsSynced) {
                $goodsReceipt->status = 'in-warehouse';
                $goodsReceipt->save();

                // Update purchase order status to received
                $goodsReceipt->purchaseOrder->status = 'received';
                $goodsReceipt->purchaseOrder->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Goods receipt detail synced successfully',
                'data' => $goodsReceiptDetail
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
