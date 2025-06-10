<?php

namespace App\Http\Controllers\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\GoodsReceipt;

class GoodsReceiptController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\GoodsReceipt::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/WarehouseManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create");
    }

    public function show($id)
    {
        $model = $this->modelClass::with([
            'company',
            'purchaseOrder',
            'purchaseOrder.supplier',
            'purchaseOrder.warehouse',
            'details',
            'details.purchaseOrderDetail',
            'details.purchaseOrderDetail.supplierProductDetail',
            'details.purchaseOrderDetail.supplierProductDetail.product',
            'details.purchaseOrderDetail.supplierProductDetail.variation',
            'details.serials'
        ])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
        ]);
    }

    public function settings($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Settings", [
            'modelData' => $model,
        ]);
    }

    public function export()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Export");
    }

    public function print(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'company',
            'purchaseOrder',
            'details.purchaseOrderDetail.supplierProductDetail.product',
            'details.purchaseOrderDetail.supplierProductDetail.variation',
            'details.serials'
        ]);

        return Inertia::render('Modules/WarehouseManagement/GoodsReceipts/Print', [
            'modelData' => $goodsReceipt
        ]);
    }

    public function printSerials(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'company',
            'purchaseOrder',
            'purchaseOrder.supplier',
            'purchaseOrder.warehouse',
            'details',
            'details.purchaseOrderDetail',
            'details.purchaseOrderDetail.supplierProductDetail',
            'details.purchaseOrderDetail.supplierProductDetail.productVariation',
            'details.purchaseOrderDetail.supplierProductDetail.productVariation.product',
            'details.serials'
        ]);

        return Inertia::render('Modules/WarehouseManagement/GoodsReceipts/PrintSerials', [
            'modelData' => $goodsReceipt
        ]);
    }
}