<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    public function index()
    {
        $requisitions = PurchaseRequisition::with(['company', 'warehouse', 'creator'])
            ->latest()
            ->paginate(10);

        return response()->json($requisitions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = collect($request->items)->sum('total_price');

            $requisition = PurchaseRequisition::create([
                'number' => 'PR-' . date('YmdHis'),
                'company_id' => $request->company_id,
                'warehouse_id' => $request->warehouse_id,
                'status' => 'pending',
                'notes' => $request->notes,
                'total_amount' => $totalAmount,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $requisition->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase Requisition created successfully',
                'requisition' => $requisition->load(['company', 'warehouse', 'items.product'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create Purchase Requisition'], 500);
        }
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        return response()->json(
            $purchaseRequisition->load(['company', 'warehouse', 'items.product', 'creator', 'approver'])
        );
    }

    public function update(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'pending') {
            return response()->json(['message' => 'Cannot update non-pending requisition'], 422);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = collect($request->items)->sum('total_price');

            $purchaseRequisition->update([
                'notes' => $request->notes,
                'total_amount' => $totalAmount,
            ]);

            // Delete existing items and create new ones
            $purchaseRequisition->items()->delete();

            foreach ($request->items as $item) {
                $purchaseRequisition->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase Requisition updated successfully',
                'requisition' => $purchaseRequisition->load(['company', 'warehouse', 'items.product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update Purchase Requisition'], 500);
        }
    }

    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        if ($purchaseRequisition->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete non-pending requisition'], 422);
        }

        try {
            DB::beginTransaction();
            $purchaseRequisition->items()->delete();
            $purchaseRequisition->delete();
            DB::commit();

            return response()->json(['message' => 'Purchase Requisition deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete Purchase Requisition'], 500);
        }
    }

    public function autocomplete(Request $request)
    {
        $query = PurchaseRequisition::query()
            ->with(['company', 'warehouse'])
            ->when($request->search, function ($query, $search) {
                $query->where('number', 'like', "%{$search}%");
            })
            ->when($request->company_id, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            });

        $requisitions = $query->limit(10)->get();

        return response()->json($requisitions);
    }
} 