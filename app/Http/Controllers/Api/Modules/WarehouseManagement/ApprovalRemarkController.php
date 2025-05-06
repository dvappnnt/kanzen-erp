<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApprovalRemarkController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\ApprovalRemark::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['purchaseOrder', 'goodsReceipt', 'user'])->latest()->paginate(perPage: 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'goods_receipt_id' => 'nullable|exists:goods_receipts,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string|max:2048',
        ]);

        $validated['user_id'] = auth()->user()->id;
        $modelData = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $modelData,
            'message' => "{$this->modelName} '{$modelData->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $modelData = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'goods_receipt_id' => 'nullable|exists:goods_receipts,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string|max:2048',
        ]);

        $modelData->update($validated);

        return response()->json([
            'modelData' => $modelData,
            'message' => "{$this->modelName} '{$modelData->name}' updated successfully.",
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
}
