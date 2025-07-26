<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\InternalTransferItem;
use App\Models\MaterialRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InternalTransferController extends Controller
{
    public function index()
    {
        $transfers = InternalTransfer::with(['materialRequest', 'fromWarehouse', 'toWarehouse'])->latest()->get();
        return Inertia::render('Modules/WarehouseManagement/InternalTransfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(Request $request)
    {
        $materialRequest = null;

        if ($request->filled('material_request_id')) {
            $materialRequest = MaterialRequest::with('warehouse', 'items.product')
                ->find($request->material_request_id);
        }

        return Inertia::render('Modules/WarehouseManagement/InternalTransfers/Create', [
            'material_requests' => MaterialRequest::select('id', 'reference_no')->get(),
            'warehouses' => Warehouse::select('id', 'name')->get(),
            'selected_material_request' => $materialRequest,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference_no' => 'required|unique:internal_transfers',
            'material_request_id' => 'required|exists:material_requests,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'status' => 'required|in:pending,approved,transferred',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|numeric|min:1',
        ]);

        $internalTransfer = InternalTransfer::create($validated);

        foreach ($validated['items'] as $itemId => $qty) {
            $internalTransfer->items()->create([
                'material_request_item_id' => $itemId,
                'quantity' => $qty,
            ]);
        }

        return redirect()->route('internal-transfers.index')->with('success', 'Internal Transfer created.');
    }


    public function show(InternalTransfer $internalTransfer)
    {
        $internalTransfer->load([
            'materialRequest.items.product',
            'items.requestItem.product', // Corrected relationship name
            'fromWarehouse',
            'toWarehouse',
        ]);

        return Inertia::render('Modules/WarehouseManagement/InternalTransfers/Show', [
            'transfer' => $internalTransfer,
        ]);
    }



    public function edit(InternalTransfer $internalTransfer)
    {
        $internalTransfer->load([
            'materialRequest.items.product',
            'items.requestItem', // 👈 Add this line
        ]);

        return Inertia::render('Modules/WarehouseManagement/InternalTransfers/Edit', [
            'transfer' => $internalTransfer,
            'material_requests' => MaterialRequest::select('id', 'reference_no')->get(),
            'warehouses' => Warehouse::select('id', 'name')->get(),
        ]);
    }


    public function update(Request $request, InternalTransfer $internalTransfer)
    {
        $validated = $request->validate([
            // 'material_request_id' => 'required|exists:material_requests,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'status' => 'required|in:pending,approved,transferred',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|numeric|min:0',
        ]);

        $internalTransfer->update($validated);

        // Sync or update items (example only if you have a pivot table or relation)
        foreach ($validated['items'] as $itemId => $qty) {
            // Store to pivot or custom InternalTransferItem model
            InternalTransferItem::updateOrCreate(
                ['internal_transfer_id' => $internalTransfer->id, 'material_request_item_id' => $itemId],
                ['quantity' => $qty]
            );
        }

        return redirect()->route('internal-transfers.index')->with('success', 'Internal Transfer updated.');
    }
}
