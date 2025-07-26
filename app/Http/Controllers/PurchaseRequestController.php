<?php

namespace App\Http\Controllers;

use App\Models\{PurchaseRequest, MaterialRequest, PurchaseRequestItem, Warehouse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['materialRequest', 'warehouse'])->latest()->get();
        return Inertia::render('Modules/WarehouseManagement/PurchaseRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Modules/WarehouseManagement/PurchaseRequests/Create', [
            'material_requests' => MaterialRequest::select('id', 'reference_no')->get(),
            'warehouses' => Warehouse::select('id', 'name')->get(),
            'pre_selected_material_request_id' => $request->query('material_request_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_request_id' => 'required|exists:material_requests,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'nullable|in:pending,approved,rejected',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|numeric|min:0',
        ]);

        $validated['reference_no'] = 'PR-' . now()->format('Ymd-His') . '-' . strtoupper(str()->random(4));
        $validated['status'] = $validated['status'] ?? 'pending';

        DB::transaction(function () use ($validated) {
            $pr = PurchaseRequest::create($validated);

            foreach ($validated['items'] as $itemId => $qty) {
                if ($qty > 0) {
                    PurchaseRequestItem::create([
                        'purchase_request_id' => $pr->id,
                        'material_request_item_id' => $itemId,
                        'quantity' => $qty,
                    ]);
                }
            }
        });

        return redirect()->route('purchase-requests.index')->with('success', 'Purchase Request created.');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['materialRequest', 'warehouse', 'items.materialRequestItem.product']);
        return Inertia::render('Modules/WarehouseManagement/PurchaseRequests/Show', [
            'request' => $purchaseRequest,
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['materialRequest', 'warehouse', 'items.materialRequestItem.product']);

        return Inertia::render('Modules/WarehouseManagement/PurchaseRequests/Edit', [
            'request' => $purchaseRequest,
            'material_requests' => MaterialRequest::select('id', 'reference_no')->get(),
            'warehouses' => Warehouse::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $validated = $request->validate([
            // 'material_request_id' => 'required|exists:material_requests,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'required|in:pending,approved,rejected',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $purchaseRequest->update($validated);

            foreach ($validated['items'] as $itemId => $qty) {
                PurchaseRequestItem::updateOrCreate(
                    [
                        'purchase_request_id' => $purchaseRequest->id,
                        'material_request_item_id' => $itemId,
                    ],
                    ['quantity' => $qty]
                );
            }
        });

        return redirect()->route('purchase-requests.index')->with('success', 'Purchase Request updated.');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
        return redirect()->back()->with('success', 'Purchase Request deleted.');
    }
}
