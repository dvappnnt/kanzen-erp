<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierProductController extends Controller
{
    public function index(Supplier $supplier)
    {
        return $supplier->products()->latest()->paginate(10);
    }

    public function show(Supplier $supplier, $id)
    {
        return $supplier->products()->findOrFail($id);
    }

    public function store(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = $supplier->products()->create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    public function update(Request $request, Supplier $supplier, $id)
    {
        $product = $supplier->products()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    public function destroy(Supplier $supplier, $id)
    {
        $product = $supplier->products()->findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function restore(Supplier $supplier, $id)
    {
        $product = $supplier->products()->withTrashed()->findOrFail($id);
        $product->restore();

        return response()->json([
            'message' => 'Product restored successfully',
            'data' => $product
        ]);
    }

    public function variations(Supplier $supplier, Product $product)
    {
        $variations = \App\Models\SupplierProductVariation::where('supplier_id', $supplier->id)
            ->whereHas('variation', function($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->with(['variation'])
            ->get();

        return response()->json([
            'data' => $variations
        ]);
    }

    public function updateVariations(Request $request, Supplier $supplier, Product $product)
    {
        $validated = $request->validate([
            'variations' => 'required|array',
            'variations.*.product_variation_id' => [
                'required',
                'exists:product_variations,id',
                function ($attribute, $value, $fail) use ($product) {
                    $variation = \App\Models\ProductVariation::find($value);
                    if ($variation && $variation->product_id !== $product->id) {
                        $fail('The selected variation does not belong to this product.');
                    }
                }
            ],
            'variations.*.sku' => 'required|string|max:100',
            'variations.*.barcode' => 'nullable|string|max:100',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.cost' => 'required|numeric|min:0',
            'variations.*.lead_time_days' => 'required|integer|min:0'
        ]);

        foreach ($validated['variations'] as $variationData) {
            \App\Models\SupplierProductVariation::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'product_variation_id' => $variationData['product_variation_id']
                ],
                [
                    'sku' => $variationData['sku'],
                    'barcode' => $variationData['barcode'],
                    'price' => $variationData['price'],
                    'cost' => $variationData['cost'],
                    'lead_time_days' => $variationData['lead_time_days']
                ]
            );
        }

        return response()->json([
            'message' => 'Supplier product variations updated successfully'
        ]);
    }
} 