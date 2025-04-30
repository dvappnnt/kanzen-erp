<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseProduct;
use App\Models\WarehouseProductSerial;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\GoodsReceipt::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['company', 'purchaseOrder', 'purchaseOrder.supplier','purchaseOrder.company','purchaseOrder.warehouse'])->latest()->paginate(perPage: 10);
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

    public function transfer($id)
    {
        try {
            DB::beginTransaction();

            $goodsReceipt = $this->modelClass::with([
                'details.purchaseOrderDetail.supplierProductDetail',
                'details.serials',
                'purchaseOrder.warehouse'
            ])->findOrFail($id);

            // Check if all items are fully received
            foreach ($goodsReceipt->details as $detail) {
                if ($detail->expected_qty !== $detail->received_qty) {
                    throw new \Exception('All items must be fully received before transfer');
                }
            }

            // Create warehouse transfer record
            $transfer = WarehouseTransfer::create([
                'goods_receipt_id' => $goodsReceipt->id,
                'destination_warehouse_id' => $goodsReceipt->purchaseOrder->warehouse_id,
                'created_by_user_id' => request()->user()->id
            ]);

            // Process each detail
            foreach ($goodsReceipt->details as $detail) {
                // Find or create warehouse product
                $warehouseProduct = WarehouseProduct::firstOrCreate(
                    [
                        'warehouse_id' => $goodsReceipt->purchaseOrder->warehouse_id,
                        'supplier_product_detail_id' => $detail->purchaseOrderDetail->supplier_product_detail_id,
                    ],
                    [
                        'qty' => 0,
                        'has_serials' => count($detail->serials) > 0,
                        'price' => $detail->purchaseOrderDetail->price,
                        'last_cost' => $detail->purchaseOrderDetail->price
                    ]
                );

                // Update quantity
                $warehouseProduct->qty += $detail->received_qty;
                $warehouseProduct->save();

                // Transfer serials if any
                if ($detail->serials->count() > 0) {
                    foreach ($detail->serials as $serial) {
                        WarehouseProductSerial::create([
                            'warehouse_product_id' => $warehouseProduct->id,
                            'serial_number' => $serial->serial_number,
                            'batch_number' => $serial->batch_number,
                            'manufactured_at' => $serial->manufactured_at,
                            'expired_at' => $serial->expired_at
                        ]);
                    }
                }
            }

            // Update goods receipt status - this will trigger the model events
            // that will update the purchase order status
            $goodsReceipt->status = 'in-warehouse';
            $goodsReceipt->save();

            DB::commit();

            return response()->json([
                'message' => 'Items transferred to warehouse successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
