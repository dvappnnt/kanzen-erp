<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupplierInvoicePayment;
use Illuminate\Support\Facades\Storage;

class SupplierInvoicePaymentController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\SupplierInvoicePayment::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['supplierInvoice', 'companyAccount'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_invoice_id' => 'required|exists:supplier_invoices,id',
            'company_account_id' => 'nullable|exists:company_accounts,id',
            'payment_method' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'payment_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('payment-files', 'public');
            $validated['file_path'] = $path;
        }

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['supplierInvoice', 'companyAccount'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'supplier_invoice_id' => 'required|exists:supplier_invoices,id',
            'company_account_id' => 'nullable|exists:company_accounts,id',
            'payment_method' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'payment_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($model->file_path) {
                Storage::disk('public')->delete($model->file_path);
            }
            
            $file = $request->file('file');
            $path = $file->store('payment-files', 'public');
            $validated['file_path'] = $path;
        }

        $model->update($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ]);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        
        // Only allow deletion if status is pending
        if ($model->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete payment that is not in pending status.'
            ], 422);
        }

        // Delete file if exists
        if ($model->file_path) {
            Storage::disk('public')->delete($model->file_path);
        }

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

        $models = $this->modelClass::with(['supplierInvoice', 'companyAccount'])
            ->where('reference_number', 'like', "%{$searchTerm}%")
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

    public function approve(Request $request, SupplierInvoicePayment $supplierInvoicePayment)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($supplierInvoicePayment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve payments in pending status.'
            ], 422);
        }

        $supplierInvoicePayment->update([
            'status' => 'approved',
            'remarks' => $validated['remarks'] ?? null
        ]);

        return response()->json(['message' => 'Supplier invoice payment approved successfully.']);
    }

    public function reject(Request $request, SupplierInvoicePayment $supplierInvoicePayment)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($supplierInvoicePayment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only reject payments in pending status.'
            ], 422);
        }

        $supplierInvoicePayment->update([
            'status' => 'rejected',
            'remarks' => $validated['remarks'] ?? null
        ]);

        return response()->json(['message' => 'Supplier invoice payment rejected successfully.']);
    }
}
