<?php

namespace App\Http\Controllers;

use App\Models\StockAlertThreshold;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockAlertThresholdController extends Controller
{
 public function index(Request $request)
{
    $query = StockAlertThreshold::with(['warehouse', 'product']);

    if ($request->filled('warehouse_id')) {
        $query->where('warehouse_id', $request->warehouse_id);
    }

    if ($request->filled('product_id')) {
        $query->where('product_id', $request->product_id);
    }

    $thresholds = $query->paginate(10)->withQueryString();

    $warehouses = Warehouse::select('id', 'name')->get();

    // Only get products that exist in warehouse_products (same as in create)
    $products = Product::whereIn('id', function ($query) {
        $query->select('product_id')
            ->from('supplier_product_details')
            ->whereIn('id', function ($subQuery) {
                $subQuery->select('supplier_product_detail_id')
                    ->from('warehouse_products');
            });
    })->select('id', 'name')->get();

    return Inertia::render('Modules/StockAlertThresholds/Index', [
        'thresholds' => $thresholds,
        'warehouses' => $warehouses,
        'products' => $products,
        'filters' => $request->only(['warehouse_id', 'product_id']),
    ]);
}



    public function create()
    {
        $warehouses = Warehouse::select('id', 'name')->get();

        // Only get products that exist in warehouse_products
        $products = Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('supplier_product_details')
                ->whereIn('id', function ($subQuery) {
                    $subQuery->select('supplier_product_detail_id')->from('warehouse_products');
                });
        })->select('id', 'name')->get();

        return Inertia::render('Modules/StockAlertThresholds/Create', [
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'min_qty' => 'required|numeric|min:0',
        ]);

        StockAlertThreshold::create($validated);

        return redirect()->route('stock-alert-thresholds.index')->with('success', 'Threshold created successfully.');
    }


    public function edit($id)
    {
        $threshold = StockAlertThreshold::findOrFail($id);
        $warehouses = Warehouse::select('id', 'name')->get();

        $products = Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('supplier_product_details')
                ->whereIn('id', function ($subQuery) {
                    $subQuery->select('supplier_product_detail_id')->from('warehouse_products');
                });
        })->select('id', 'name')->get();

        return Inertia::render('Modules/StockAlertThresholds/Edit', [
            'threshold' => $threshold,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'min_qty' => 'required|numeric|min:0',
        ]);


        $threshold = StockAlertThreshold::findOrFail($id);
       $threshold->update(['min_qty' => $validated['min_qty']]);


        return redirect()->route('stock-alert-thresholds.index')->with('success', 'Threshold updated successfully.');
    }
}
