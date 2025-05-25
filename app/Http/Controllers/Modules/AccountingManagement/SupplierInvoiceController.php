<?php

namespace App\Http\Controllers\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\SupplierInvoice;

class SupplierInvoiceController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\SupplierInvoice::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/AccountingManagement';
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
            'supplier',
            'purchaseOrder',
            'details.supplierProduct.product',
            'company',
            'companyAccount',
            'goodsReceipt',
            'payments',
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

    public function export()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Export");
    }

    public function print(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load([
            'company',
            'supplier',
            'companyAccount',
            'purchaseOrder',
            'goodsReceipt',
            'details.supplierProduct.product',
            'payments'
        ]);

        return Inertia::render('Modules/AccountingManagement/SupplierInvoices/Print', [
            'modelData' => $supplierInvoice
        ]);
    }
}