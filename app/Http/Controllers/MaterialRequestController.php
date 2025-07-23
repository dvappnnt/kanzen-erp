<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MaterialRequestController extends Controller
{

    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\MaterialRequest::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/WarehouseManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }


    public function create()
    {
        $products = Product::whereIn('id', function ($query) {
            $query->select('product_id')
                ->from('supplier_product_details')
                ->whereIn('id', function ($subQuery) {
                    $subQuery->select('supplier_product_detail_id')->from('warehouse_products');
                });
        })->select('id', 'name')->get();

        return Inertia::render('Modules/WarehouseManagement/MaterialRequests/Create', [
            'products' => $products,
            'warehouses' => Warehouse::select('id', 'name')->get(),
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_qty' => 'required|numeric|min:1',
        ]);

        $referenceNo = 'MR-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));

        $materialRequest = MaterialRequest::create([
            'reference_no' => $referenceNo,
            'requested_by_user_id' => Auth::id(),
            'warehouse_id' => $validated['warehouse_id'],
            'status' => 'pending',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $materialRequest->items()->create($item);
        }

        return redirect()->route('material-requests.index')
            ->with('success', 'Material request created successfully.');
    }
    public function edit(MaterialRequest $materialRequest)
{
    $materialRequest->load(['warehouse', 'items.product']);

    return Inertia::render('Modules/WarehouseManagement/MaterialRequests/Edit', [
        'request' => $materialRequest,
        'warehouses' => Warehouse::select('id', 'name')->get(),
    ]);
}


   public function update(Request $request, MaterialRequest $materialRequest)
{
    $validated = $request->validate([
        'warehouse_id' => 'required|exists:warehouses,id',
        'status' => 'required|string|in:pending,approved,rejected',
        'items' => 'array',
        'items.*.id' => 'required|exists:material_request_items,id',
        'items.*.requested_qty' => 'required|numeric|min:1',
    ]);

    $materialRequest->update([
        'warehouse_id' => $validated['warehouse_id'],
        'status' => $validated['status'],
    ]);

    foreach ($validated['items'] as $itemData) {
        $materialRequest->items()->where('id', $itemData['id'])->update([
            'requested_qty' => $itemData['requested_qty'],
        ]);
    }

    return redirect()->route('material-requests.index')->with('success', 'Material Request updated.');
}



   public function show(MaterialRequest $materialRequest)
{
    $materialRequest->load([
        'warehouse',
        'requestedBy',
        'items.product'
    ]);

    return Inertia::render('Modules/WarehouseManagement/MaterialRequests/Show', [
        'request' => $materialRequest,
    ]);
}



    public function approve(MaterialRequest $materialRequest)
    {
        $materialRequest->update(['status' => 'approved']);

        return back()->with('success', 'Material request approved.');
    }

    public function reject(MaterialRequest $materialRequest)
    {
        $materialRequest->update(['status' => 'rejected']);

        return back()->with('success', 'Material request rejected.');
    }
}
