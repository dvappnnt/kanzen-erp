<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SupplierProductController extends Controller
{
    public function index(Supplier $supplier)
    {
        return $supplier->products()
            ->with([
                'variations.attributes.attribute',
                'variations.attributes.attributeValue',
                'supplierProductDetails' => function($query) use ($supplier) {
                    $query->where('supplier_id', $supplier->id)
                        ->with(['variation.attributes.attribute', 'variation.attributes.attributeValue']);
                }
            ])
            ->select('products.*')
            ->get()
            ->map(function ($product) {
                // Format variation names in the main variations array
                if ($product->variations) {
                    $product->variations = $product->variations->map(function ($variation) {
                        $attributeText = $this->getAttributeText($variation);
                        $variation->name = $variation->name . $attributeText;
                        return $variation;
                    });
                }

                // Format variation names in supplier_product_details
                if ($product->supplier_product_details) {
                    $product->supplier_product_details = $product->supplier_product_details->map(function ($detail) {
                        if ($detail->variation) {
                            $attributeText = $this->getAttributeText($detail->variation);
                            $detail->variation->name = $detail->variation->name . $attributeText;
                        }
                        return $detail;
                    });
                }

                return $product;
            });
    }

    private function getAttributeText($variation)
    {
        if (!$variation->attributes || $variation->attributes->isEmpty()) {
            return '';
        }

        return ' (' . $variation->attributes->map(function ($attr) {
            return $attr->attribute->name . ': ' . $attr->attributeValue->value;
        })->join(', ') . ')';
    }

    public function show(Supplier $supplier, $id)
    {
        return $supplier->products()->findOrFail($id);
    }

    public function store(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.has_variation' => 'required|boolean',
            'products.*.details' => 'required|array|min:1',
            'products.*.details.*.product_variation_id' => 'required|exists:product_variations,id',
            'products.*.details.*.currency' => 'required|string|max:3',
            'products.*.details.*.price' => 'required|numeric|min:0',
            'products.*.details.*.cost' => 'nullable|numeric|min:0',
            'products.*.details.*.lead_time_days' => 'nullable|integer|min:0',
            'products.*.details.*.is_default' => 'nullable|boolean',
        ]);

        $results = [
            'success' => [],
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($validated['products'] as $index => $productData) {
                try {
                    // Check if product is already linked to supplier
                    $exists = DB::table('supplier_products')
                        ->where('supplier_id', $supplier->id)
                        ->where('product_id', $productData['product_id'])
                        ->exists();

                    if ($exists) {
                        $results['errors'][] = [
                            'index' => $index,
                            'message' => "Product ID {$productData['product_id']} is already linked to this supplier"
                        ];
                        continue;
                    }

                    // Create the supplier product link
                    DB::table('supplier_products')->insert([
                        'supplier_id' => $supplier->id,
                        'product_id' => $productData['product_id'],
                        'has_variation' => $productData['has_variation'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Insert supplier product details
                    foreach ($productData['details'] as $detail) {
                        DB::table('supplier_product_details')->insert([
                            'supplier_id' => $supplier->id,
                            'product_id' => $productData['product_id'],
                            'product_variation_id' => $detail['product_variation_id'],
                            'currency' => $detail['currency'],
                            'price' => $detail['price'],
                            'cost' => $detail['cost'] ?? $detail['price'],
                            'lead_time_days' => $detail['lead_time_days'] ?? 0,
                            'is_default' => $detail['is_default'] ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $results['success'][] = [
                        'index' => $index,
                        'product_id' => $productData['product_id']
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($results['success']) . ' products linked successfully' . 
                           (count($results['errors']) > 0 ? ' with ' . count($results['errors']) . ' errors' : ''),
                'results' => $results
            ], count($results['errors']) > 0 ? 207 : 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:supplier_products,id',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.has_variation' => 'required|boolean',
            'products.*.details' => 'required|array|min:1',
            'products.*.details.*.product_variation_id' => 'required|exists:product_variations,id',
            'products.*.details.*.currency' => 'required|string|max:3',
            'products.*.details.*.price' => 'required|numeric|min:0',
            'products.*.details.*.cost' => 'nullable|numeric|min:0',
            'products.*.details.*.lead_time_days' => 'nullable|integer|min:0',
            'products.*.details.*.is_default' => 'nullable|boolean',
        ]);

        $results = [
            'success' => [],
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($validated['products'] as $index => $productData) {
                try {
                    // Update the supplier product link
                    DB::table('supplier_products')
                        ->where('supplier_id', $supplier->id)
                        ->where('id', $productData['id'])
                        ->update([
                            'product_id' => $productData['product_id'],
                            'has_variation' => $productData['has_variation'],
                            'updated_at' => now(),
                        ]);

                    // Delete existing details
                    DB::table('supplier_product_details')
                        ->where('supplier_id', $supplier->id)
                        ->where('product_id', $productData['product_id'])
                        ->delete();

                    // Insert updated details
                    foreach ($productData['details'] as $detail) {
                        DB::table('supplier_product_details')->insert([
                            'supplier_id' => $supplier->id,
                            'product_id' => $productData['product_id'],
                            'product_variation_id' => $detail['product_variation_id'],
                            'currency' => $detail['currency'],
                            'price' => $detail['price'],
                            'cost' => $detail['cost'] ?? $detail['price'],
                            'lead_time_days' => $detail['lead_time_days'] ?? 0,
                            'is_default' => $detail['is_default'] ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $results['success'][] = [
                        'index' => $index,
                        'product_id' => $productData['product_id']
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($results['success']) . ' products updated successfully' . 
                           (count($results['errors']) > 0 ? ' with ' . count($results['errors']) . ' errors' : ''),
                'results' => $results
            ], count($results['errors']) > 0 ? 207 : 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process updates',
                'error' => $e->getMessage()
            ], 500);
        }
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
            ->whereHas('variation', function ($query) use ($product) {
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

    public function updateDetail(Request $request, Supplier $supplier, $productId, $detailId)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:3',
            'price' => 'required|numeric|min:0',
            'lead_time_days' => 'required|integer|min:0',
        ]);

        DB::table('supplier_product_details')
            ->where('id', $detailId)
            ->where('supplier_id', $supplier->id)
            ->where('product_id', $productId)
            ->update([
                'currency' => $validated['currency'],
                'price' => $validated['price'],
                'lead_time_days' => $validated['lead_time_days'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Product details updated successfully',
            'success' => true
        ]);
    }
}
