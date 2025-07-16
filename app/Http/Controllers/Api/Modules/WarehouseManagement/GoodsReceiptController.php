<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseProduct;
use App\Models\WarehouseProductSerial;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use App\Models\GoodsReceiptDetail;
use App\Settings\AppSettings;

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

    public function show($id, AppSettings $settings)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->receive_with_serial = $settings->receive_with_serial;
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

            // Check if there are any unsynced details
            $unsyncedDetails = $goodsReceipt->details->where('is_synced', false);
            if ($unsyncedDetails->isEmpty()) {
                throw new \Exception('All items have already been synced to warehouse');
            }

            // Create warehouse transfer record
            $transfer = WarehouseTransfer::create([
                'goods_receipt_id' => $goodsReceipt->id,
                'destination_warehouse_id' => $goodsReceipt->purchaseOrder->warehouse_id,
                'created_by_user_id' => request()->user()->id
            ]);

            // Process each unsynced detail
            foreach ($unsyncedDetails as $detail) {
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
                        'last_cost' => $detail->purchaseOrderDetail->price,
                        'sku' => $detail->purchaseOrderDetail->supplierProductDetail->productVariation->sku,
                        'barcode' => $detail->purchaseOrderDetail->supplierProductDetail->productVariation->barcode
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

                // Mark detail as synced
                $detail->is_synced = true;
                $detail->save();
            }

            // Check if all details are now synced
            $allDetailsSynced = $goodsReceipt->details()
                ->where('is_synced', false)
                ->count() === 0;

            if ($allDetailsSynced) {
                // Update goods receipt status to in-warehouse
                $goodsReceipt->status = 'in-warehouse';
                $goodsReceipt->save();

                // Update purchase order status to received
                $goodsReceipt->purchaseOrder->status = 'received';
                $goodsReceipt->purchaseOrder->save();
            }

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

    public function export(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date|filled',
            'to_date' => 'required|date|after_or_equal:from_date|filled',
            'status' => 'required|string|filled',
        ]);

        $fromDate = $validated['from_date'];
        $toDate = $validated['to_date'] ?? now()->toDateString(); // fallback to today if not provided
        $status = $validated['status'] ?? '*'; // fallback to all statuses

        $export = new \App\Exports\GoodsReceiptExport(
            $fromDate,
            $toDate,
            $status
        );

        $fileName = 'goods_receipts_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
    }
}
