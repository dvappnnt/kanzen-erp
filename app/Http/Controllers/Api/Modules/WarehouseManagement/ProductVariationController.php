<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationAttribute;
use Illuminate\Support\Facades\DB;

class ProductVariationController extends Controller
{
    public function index(Product $product)
    {
        $variations = ProductVariation::with(['attributes.attribute', 'attributes.attributeValue'])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'is_default' => $variation->is_default,
                    'attributes' => $variation->attributes->map(function ($attr) {
                        return [
                            'id' => $attr->id,
                            'attribute_id' => $attr->attribute_id,
                            'attribute_value_id' => $attr->attribute_value_id,
                            'product_variation_id' => $attr->product_variation_id,
                            'created_at' => $attr->created_at,
                            'updated_at' => $attr->updated_at,
                            'attribute' => [
                                'id' => $attr->attribute->id,
                                'name' => $attr->attribute->name
                            ],
                            'value' => [
                                'id' => $attr->attributeValue->id,
                                'value' => $attr->attributeValue->value
                            ]
                        ];
                    })
                ];
            });

        return response()->json($variations);
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.attribute_value_id' => 'required|exists:attribute_values,id'
        ]);

        try {
            DB::beginTransaction();

            // If this variation is set as default, remove default from others
            if ($validated['is_default']) {
                ProductVariation::where('product_id', $product->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false, 'name' => 'Product Variation']);
            }

            // Create the variation
            $variation = ProductVariation::create([
                'product_id' => $product->id,
                'name' => $validated['name'],
                'is_default' => $validated['is_default']
            ]);

            // Create variation attributes
            foreach ($validated['attributes'] as $attribute) {
                ProductVariationAttribute::create([
                    'product_variation_id' => $variation->id,
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_value_id' => $attribute['attribute_value_id']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Variation created successfully',
                'variation' => $variation->load('attributes.attribute', 'attributes.attributeValue')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create variation'], 500);
        }
    }

    public function update(Request $request, Product $product, $id)
    {
        $variation = ProductVariation::findOrFail($id);

        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.attribute_value_id' => 'required|exists:attribute_values,id'
        ]);

        try {
            DB::beginTransaction();

            // Delete existing attributes
            $variation->attributes()->delete();

            // Create new attributes
            foreach ($validated['attributes'] as $attribute) {
                ProductVariationAttribute::create([
                    'product_variation_id' => $variation->id,
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_value_id' => $attribute['attribute_value_id']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Variation updated successfully',
                'variation' => $variation->load('attributes.attribute', 'attributes.attributeValue')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update variation'], 500);
        }
    }

    public function destroy(Product $product, $id)
    {
        try {
            $variation = ProductVariation::findOrFail($id);

            DB::beginTransaction();

            // Delete associated attributes first
            $variation->attributes()->delete();

            // Then delete the variation
            $variation->delete();

            DB::commit();

            return response()->json(['message' => 'Variation deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete variation'], 500);
        }
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
            'product_id' => 'required|exists:products,id'
        ]);

        $searchTerm = $request->input('search');
        $productId = $request->input('product_id');

        $variations = ProductVariation::with(['attributes.attribute', 'attributes.attributeValue'])
            ->where('product_id', $productId)
            ->whereHas('attributes.attributeValue', function ($query) use ($searchTerm) {
                $query->where('value', 'like', "%{$searchTerm}%");
            })
            ->take(10)
            ->get()
            ->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'attributes' => $variation->attributes->map(function ($attr) {
                        return [
                            'id' => $attr->id,
                            'attribute_id' => $attr->attribute_id,
                            'name' => $attr->attribute->name,
                            'value' => $attr->attributeValue->value
                        ];
                    })
                ];
            });

        if ($variations->isEmpty()) {
            return response()->json([
                'message' => 'No variations found.',
            ], 404);
        }

        return response()->json([
            'data' => $variations,
            'message' => 'Variations retrieved successfully.'
        ], 200);
    }
}
