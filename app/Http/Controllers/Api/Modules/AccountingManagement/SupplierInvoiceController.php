<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierInvoiceController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\SupplierInvoice::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['supplier', 'purchaseOrder'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['supplier', 'purchaseOrder'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
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

        $models = $this->modelClass::with(['supplier', 'purchaseOrder'])
            ->where('invoice_number', 'like', "%{$searchTerm}%")
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

        $export = new \App\Exports\SupplierInvoiceExport(
            $fromDate,
            $toDate,
            $status
        );

        $fileName = 'supplier_invoices_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
    }
}