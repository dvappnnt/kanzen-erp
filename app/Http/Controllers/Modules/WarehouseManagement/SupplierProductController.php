<?php

namespace App\Http\Controllers\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\SupplierProduct;
use App\Models\SupplierProductVariation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierProductController extends Controller
{
    protected $modelClass;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\SupplierProduct::class;
        $this->modulePath = 'Modules/WarehouseManagement/Suppliers/Products';
    }

    public function index(Supplier $supplier)
    {
        return Inertia::render("{$this->modulePath}/Index", [
            'modelData' => $supplier,
            'products' => $supplier->products()->latest()->paginate(10)
        ]);
    }

    public function show(Supplier $supplier, $id)
    {
        $product = $supplier->products()->findOrFail($id);

        return Inertia::render("{$this->modulePath}/Show", [
            'modelData' => $supplier,
            'product' => $product
        ]);
    }

    public function edit(Supplier $supplier, $id)
    {
        $product = $supplier->products()->findOrFail($id);

        return Inertia::render("{$this->modulePath}/Edit", [
            'modelData' => $supplier,
            'product' => $product
        ]);
    }

    public function create(Supplier $supplier)
    {
        return Inertia::render("{$this->modulePath}/Create", [
            'modelData' => $supplier
        ]);
    }

    public function editVariations(Supplier $supplier, Product $product)
    {
        $supplierProduct = SupplierProduct::where('supplier_id', $supplier->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        // Load the product with its variations
        $product->load('variations');

        return Inertia::render("{$this->modulePath}/Variations", [
            'supplier' => $supplier,
            'product' => $product,
            'supplierProduct' => $supplierProduct
        ]);
    }
}
