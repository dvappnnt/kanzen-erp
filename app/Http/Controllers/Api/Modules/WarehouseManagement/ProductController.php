<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Product;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Product::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['category'])->latest()->paginate(perPage: 50);
    }

    public function complete()
    {
        return $this->modelClass::with(['category'])->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:1024',
            'avatar' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,avif|max:2048',
            'has_variation' => 'nullable', // just "required" here, no boolean check
        ]);

        // Force has_variation to real boolean
        $validated['has_variation'] = filter_var($validated['has_variation'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('companies/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $validated['created_by_user_id'] = auth()->user()->id;

        $company = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $company,
            'message' => "{$this->modelName} '{$company->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:1024',
            'avatar' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,avif|max:2048',
            'has_variation' => 'nullable', // just "required" here, no boolean check
        ]);

        // Force has_variation to real boolean
        $validated['has_variation'] = filter_var($validated['has_variation'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('avatar')) {
            if ($model->avatar && \Storage::disk('public')->exists($model->avatar)) {
                \Storage::disk('public')->delete($model->avatar);
            }

            $avatarPath = $request->file('avatar')->store('companies/avatars', 'public');
            $validated['avatar'] = $avatarPath;
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

        $models = $this->modelClass::with(['category'])
            ->where('name', 'like', "%{$searchTerm}%")
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $rows = Excel::toArray([], $file)[0]; // Get the first sheet

        // Assume first row is header
        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        $productsToInsert = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $row = array_combine($header, $row);

                // 1. Check company
                $company = Company::where('name', trim($row['Company']))->first();
                if (!$company) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Company '{$row['Company']}' does not exist."
                    ], 422);
                }

                // 2. Check or create category
                $category = Category::firstOrCreate(
                    ['name' => trim($row['Category'])],
                    ['related_model' => 'products']
                );

                // 3. Prepare product data
                $productData = [
                    'name' => $row['Name'],
                    'category_id' => $category->id,
                    'company_id' => $company->id,
                    'description' => $row['Description'] ?? null,
                    'unit_of_measure' => $row['Measure'] ?? null,
                    'avatar' => $row['Avatar (if any)'] ?? null,
                    'has_variation' => 1, // or parse from row if present
                    // 'slug' => Str::slug($row['Name']) . '-' . Str::random(5),
                    // Add other fields as needed
                ];

                // 4. Insert product
                Product::create($productData);
            }
            DB::commit();
            return response()->json(['message' => 'Import successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
