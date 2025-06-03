<?php

namespace App\Http\Controllers\Api\Modules\HumanResourceManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\EmployeeDocument::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['employee', 'documentType']);

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->whereHas('documentType', function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by employee_id if provided
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return $query->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'document_type_id' => 'required|exists:document_types,id',
            'file_path' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
        ]);

        if ($request->hasFile('file_path')) {
            $path = $request->file('file_path')->store('employee-documents', 'public');
            $validated['file_path'] = $path;
        }

        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['employee', 'documentType'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'file_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
        ]);

        if ($request->hasFile('file_path')) {
            // Delete old file if exists
            if ($model->file_path) {
                Storage::disk('public')->delete($model->file_path);
            }
            
            $path = $request->file('file_path')->store('employee-documents', 'public');
            $validated['file_path'] = $path;
        }

        $updated = $model->update($validated);

        if (!$updated) {
            return response()->json([
                'message' => "Failed to update {$this->modelName}.",
            ], 500);
        }

        // Refresh the model to get updated data
        $model->refresh();

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} updated successfully.",
        ], 200);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        
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

        $models = $this->modelClass::with(['employee', 'documentType'])
            ->whereHas('documentType', function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            })
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

    public function download($id)
    {
        $model = $this->modelClass::findOrFail($id);

        if (!$model->file_path || !Storage::disk('public')->exists($model->file_path)) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        $path = Storage::disk('public')->path($model->file_path);
        $filename = basename($model->file_path);
        $mimeType = mime_content_type($path);

        return response()->download($path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}