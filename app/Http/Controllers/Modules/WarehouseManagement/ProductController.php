<?php

namespace App\Http\Controllers\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Category;

class ProductController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\Product::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Modules/WarehouseManagement';
    }

    public function index()
    {
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index");
    }

    public function create()
    {
        $query = Category::where('related_model', $this->modelName);
        $categories = $query->orderBy('name', 'asc')->get();
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'categories' => $categories,
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Show", [
            'modelData' => $model,
        ]);
    }

    public function edit($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);
        
        $query = Category::where('related_model', $this->modelName);
        $categories = $query->orderBy('name', 'asc')->get();
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'modelData' => $model,
            'categories' => $categories,
        ]);
    }

    public function specifications($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);
        $specifications = $model->specifications;

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Specifications", [
            'modelData' => $model,
            'specifications' => $specifications,
        ]);
    }

    public function variations($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);
        $variations = $model->variations()->with(['attributes.attribute', 'attributes.attributeValue'])->get()
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

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Variations", [
            'modelData' => $model,
            'variations' => $variations,
        ]);
    }

    public function images($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);
        $images = $model->images;

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Images", [
            'modelData' => $model,
            'images' => $images,
        ]);
    }
}