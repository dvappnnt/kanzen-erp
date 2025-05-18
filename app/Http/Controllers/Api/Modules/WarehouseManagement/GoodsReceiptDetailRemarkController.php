<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoodsReceiptDetailRemark;
use App\Models\GoodsReceiptSerial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GoodsReceiptDetailRemarkController extends Controller
{
    /**
     * Store a newly created remark in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'goods_receipt_detail_id' => [
                'required',
                'exists:goods_receipt_details,id',
            ],
            'goods_receipt_serial_id' => [
                'nullable',
                'exists:goods_receipt_serials,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $serial = GoodsReceiptSerial::find($value);
                        if (!$serial || $serial->goods_receipt_detail_id != $request->goods_receipt_detail_id) {
                            $fail('The selected serial number does not belong to the specified goods receipt detail.');
                        }
                    }
                }
            ],
            'status' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:1000',
            'is_serial' => 'boolean'
        ]);

        try {
            // Create the remark with the current user's ID
            $remark = GoodsReceiptDetailRemark::create([
                'goods_receipt_detail_id' => $validated['goods_receipt_detail_id'],
                'goods_receipt_serial_id' => $validated['goods_receipt_serial_id'] ?? null,
                'user_id' => Auth::id(),
                'status' => $validated['status'],
                'remarks' => $validated['remarks'],
                'is_serial' => $validated['is_serial'] ?? false
            ]);

            return response()->json([
                'message' => 'Remark created successfully',
                'data' => $remark
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create remark',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
