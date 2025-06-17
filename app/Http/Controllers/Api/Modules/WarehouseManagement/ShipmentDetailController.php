<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShipmentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Shipment;

class ShipmentDetailController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\ShipmentDetail::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::latest()->paginate(perPage: 10);
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
        $model = $this->modelClass::with(['invoice', 'courier', 'courierDriver', 'courierVehicle'])->findOrFail($id);
        return $model;
    }

    private function updateShipmentStatus(Shipment $shipment)
    {
        $details = $shipment->details;
        $totalDetails = $details->count();
        $deliveredDetails = $details->where('status', 'delivered')->count();
        
        $newStatus = 'pending';
        if ($deliveredDetails === $totalDetails) {
            $newStatus = 'fully-delivered';
        } else if ($deliveredDetails > 0) {
            $newStatus = 'partially-delivered';
        }

        $shipment->update(['status' => $newStatus]);
    }

    public function update(Request $request, $id)
    {
        $detail = ShipmentDetail::findOrFail($id);

        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'tracking_number' => 'required|string',
            'tracking_url' => 'nullable|url',
            'shipment_date' => 'required|date'
        ]);

        $detail->update($validated);
        $this->updateShipmentStatus($detail->shipment);

        return response()->json([
            'message' => 'Shipment detail updated successfully',
            'data' => $detail
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

    public function forPickup(Request $request, $id)
    {
        $detail = ShipmentDetail::findOrFail($id);
        
        if ($detail->status !== 'pending') {
            return response()->json(['message' => 'Shipment detail must be in pending status to mark for pickup'], 422);
        }

        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'shipment_date' => 'required|date',
            'destination' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $detail->update([
            'status' => 'for-pickup',
            'courier_id' => $validated['courier_id'],
            'shipment_date' => $validated['shipment_date'],
            'destination' => $validated['destination'],
            'notes' => $validated['notes']
        ]);

        $this->updateShipmentStatus($detail->shipment);

        return response()->json(['message' => 'Shipment detail marked for pickup successfully']);
    }

    public function inTransit(Request $request, $id)
    {
        $detail = ShipmentDetail::findOrFail($id);
        
        if ($detail->status !== 'for-pickup') {
            return response()->json(['message' => 'Shipment detail must be in for-pickup status to mark in transit'], 422);
        }

        $validated = $request->validate([
            'tracking_number' => 'required|string',
            'tracking_url' => 'nullable|url'
        ]);

        $detail->update([
            'status' => 'in-transit',
            'tracking_number' => $validated['tracking_number'],
            'tracking_url' => $validated['tracking_url']
        ]);

        $this->updateShipmentStatus($detail->shipment);

        return response()->json(['message' => 'Shipment detail marked in transit successfully']);
    }

    public function delivered(Request $request, $id)
    {
        $detail = ShipmentDetail::findOrFail($id);
        
        if ($detail->status !== 'in-transit') {
            return response()->json(['message' => 'Shipment detail must be in in-transit status to mark as delivered'], 422);
        }

        $validated = $request->validate([
            'delivered_date' => 'required|date'
        ]);

        $detail->update([
            'status' => 'delivered',
            'delivered_date' => $validated['delivered_date']
        ]);

        $this->updateShipmentStatus($detail->shipment);

        return response()->json(['message' => 'Shipment detail marked as delivered successfully']);
    }
}
