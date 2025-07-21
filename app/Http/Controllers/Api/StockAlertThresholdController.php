<?php

namespace App\Http\Controllers;

use App\Models\StockAlertThreshold;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;

class StockAlertThresholdController extends Controller
{
    public function index()
    {
        $thresholds = StockAlertThreshold::with(['warehouse', 'product'])->paginate(10);
        return response()->json($thresholds);
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'min_qty' => 'required|numeric|min:0',
        ]);

        $threshold = StockAlertThreshold::create($request->all());
        return response()->json([
            'message' => 'Threshold created successfully.',
            'data' => $threshold
        ]);
    }

    public function show($id)
    {
        $threshold = StockAlertThreshold::with(['warehouse', 'product'])->findOrFail($id);
        return response()->json($threshold);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'min_qty' => 'required|numeric|min:0',
        ]);

        $threshold = StockAlertThreshold::findOrFail($id);
        $threshold->update($request->all());

        return response()->json([
            'message' => 'Threshold updated successfully.',
            'data' => $threshold
        ]);
    }

    public function destroy($id)
    {
        $threshold = StockAlertThreshold::findOrFail($id);
        $threshold->delete();

        return response()->json([
            'message' => 'Threshold deleted successfully.'
        ]);
    }
}
