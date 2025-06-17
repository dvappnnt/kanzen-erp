<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ShipmentController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Shipment::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['invoice', 'createdByUser', 'invoice.customer'])->latest()->paginate(perPage: 10);
    }

    public function complete()
    {
        return $this->modelClass::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'courier_id' => 'required|exists:couriers,id',
            'courier_driver_id' => 'required|exists:courier_drivers,id',
            'courier_vehicle_id' => 'required|exists:courier_vehicles,id',
            'shipment_date' => 'required|date',
            'delivered_date' => 'nullable|date',
            'file_path' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($request->hasFile('file_path')) {
            $filePath = $request->file('file_path')->store('shipments/files', 'public');
            $validated['file_path'] = $filePath;
        }

        $validated['created_by_user_id'] = Auth::id();
        $modelData = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $modelData,
            'message' => "{$this->modelName} '{$modelData->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['invoice', 'details', 'details.courier', 'details.courierDriver', 'details.courierVehicle'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'courier_id' => 'required|exists:couriers,id',
            'courier_driver_id' => 'required|exists:courier_drivers,id',
            'courier_vehicle_id' => 'required|exists:courier_vehicles,id',
            'shipment_date' => 'required|date',
            'delivered_date' => 'nullable|date',
            'file_path' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($request->hasFile('file_path')) {
            if ($model->file_path && Storage::disk('public')->exists($model->file_path)) {
                Storage::disk('public')->delete($model->file_path);
            }

            $filePath = $request->file('file_path')->store('shipments/files', 'public');
            $validated['file_path'] = $filePath;
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

        $models = $this->modelClass::where('number', 'like', "%{$searchTerm}%")
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
}
