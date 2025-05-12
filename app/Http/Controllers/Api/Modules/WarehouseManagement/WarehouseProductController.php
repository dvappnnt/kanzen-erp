<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\DB;

class WarehouseProductController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = WarehouseProduct::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id'
        ]);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        $products = $warehouse->products()
            ->with('supplierProductDetail.product')
            ->get();

        return response()->json([
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ], 200);
    }

    public function show($id)
    {
        $product = $this->modelClass::with(['supplierProductDetail.product'])
            ->findOrFail($id);

        return response()->json([
            'data' => $product,
            'message' => 'Product retrieved successfully'
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'critical_level_qty' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product = $this->modelClass::findOrFail($id);
            $product->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
            'warehouse_id' => 'required|exists:warehouses,id'
        ]);

        $searchTerm = $request->input('search');
        $warehouseId = $request->input('warehouse_id');

        $models = $this->modelClass::with(['supplierProductDetail.product'])
            ->where('warehouse_id', $warehouseId)
            ->whereHas('supplierProductDetail.product', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('slug', 'like', "%{$searchTerm}%");
            })
            ->get();

        if ($models->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => "No {$this->modelName}s found."
            ], 200);
        }

        return response()->json([
            'data' => $models,
            'message' => "{$this->modelName}s retrieved successfully."
        ], 200);
    }

    public function search(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'search' => 'nullable|string',
            'category' => 'nullable|exists:categories,id'
        ]);

        $query = $this->modelClass::with(['supplierProductDetail.product'])
            ->where('warehouse_id', $request->warehouse_id);

        if ($request->search) {
            $query->whereHas('supplierProductDetail.product', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->whereHas('supplierProductDetail.product', function ($q) use ($request) {
                $q->where('category_id', $request->category);
            });
        }

        $products = $query->get();

        return response()->json([
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ], 200);
    }

    public function serialCheck(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'serial_number' => 'required|string',
            'product_id' => 'required|exists:warehouse_products,id'
        ]);

        $serial = DB::table('warehouse_product_serials')
            ->join('warehouse_products', 'warehouse_product_serials.warehouse_product_id', '=', 'warehouse_products.id')
            ->where('warehouse_products.warehouse_id', $request->warehouse_id)
            ->where('warehouse_products.id', $request->product_id)
            ->where('warehouse_product_serials.serial_number', $request->serial_number)
            ->where('warehouse_product_serials.is_sold', 0)
            ->whereNull('warehouse_product_serials.deleted_at')
            ->select('warehouse_product_serials.*', 'warehouse_products.id as product_id')
            ->first();

        return response()->json([
            'data' => $serial,
            'message' => $serial ? 'Serial/batch number found' : 'Serial/batch number not found, already sold, or does not belong to this product'
        ], 200);
    }
}
