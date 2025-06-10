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

    public function products($id)
    {
        $warehouse = $this->modelClass::findOrFail($id);

        $query = $warehouse->products()
            ->withCount('stockAdjustments')
            ->withCount('stockTransfers')
            ->with([
                'supplierProductDetail.product',
                'supplierProductDetail.variation',
                'serials',
                'transfers.originWarehouse',
                'transfers.destinationWarehouse',
                'transfers.createdByUser:id,name',
                'transfers.goodsReceipt:id,number'
            ]);

        // Apply filters
        if (request()->has('product')) {
            $query->whereHas('supplierProductDetail.product', function ($q) {
                $q->where('name', 'like', '%' . request('product') . '%');
            });
        }

        if (request()->has('variation')) {
            $query->whereHas('supplierProductDetail.variation', function ($q) {
                $q->where('name', 'like', '%' . request('variation') . '%');
            });
        }

        if (request()->has('min_qty')) {
            $query->where('qty', '>=', request('min_qty'));
        }

        if (request()->has('max_qty')) {
            $query->where('qty', '<=', request('max_qty'));
        }

        if (request()->has('min_price')) {
            $query->where('price', '>=', request('min_price'));
        }

        if (request()->has('max_price')) {
            $query->where('price', '<=', request('max_price'));
        }

        // Get total count before pagination
        $total = $query->count();

        // If total results are less than or equal to default per page,
        // or if we're not filtering, return all results without pagination
        if ($total <= 10 && !request()->hasAny(['product', 'variation', 'min_qty', 'max_qty', 'min_price', 'max_price'])) {
            $products = $query->get();
            return response()->json([
                'data' => $products,
                'message' => 'Warehouse products retrieved successfully'
            ]);
        }

        // Otherwise, return paginated results
        $products = $query->paginate(request('per_page', 10));
        return response()->json($products);
    }
}
