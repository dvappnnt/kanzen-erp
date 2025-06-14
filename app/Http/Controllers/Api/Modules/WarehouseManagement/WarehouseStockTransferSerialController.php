<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseStockTransferSerial;
use App\Models\WarehouseStockTransferDetail;
use Illuminate\Support\Facades\DB;

class WarehouseStockTransferSerialController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseStockTransferSerial::query();
        if ($request->has('warehouse_stock_transfer_detail_id')) {
            $query->where('warehouse_stock_transfer_detail_id', $request->warehouse_stock_transfer_detail_id);
        }
        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_stock_transfer_id' => 'required|exists:warehouse_stock_transfers,id',
            'warehouse_stock_transfer_detail_id' => 'required|exists:warehouse_stock_transfer_details,id',
            'serial_number' => 'nullable|string',
            'batch_number' => 'nullable|string',
            'manufactured_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
        ]);
        $serial = WarehouseStockTransferSerial::create($validated);
        return response()->json(['data' => $serial, 'message' => 'Serial created successfully.']);
    }

    public function show($id)
    {
        $serial = WarehouseStockTransferSerial::findOrFail($id);
        return response()->json(['data' => $serial]);
    }

    public function update(Request $request, $id)
    {
        $serial = WarehouseStockTransferSerial::findOrFail($id);
        $validated = $request->validate([
            'serial_number' => 'nullable|string',
            'batch_number' => 'nullable|string',
            'manufactured_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
        ]);
        $serial->update($validated);
        return response()->json(['data' => $serial, 'message' => 'Serial updated successfully.']);
    }

    public function destroy($id)
    {
        $serial = WarehouseStockTransferSerial::findOrFail($id);
        $serial->delete();
        return response()->json(['message' => 'Serial deleted successfully.']);
    }
} 