<?php

namespace App\Http\Controllers\Api\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\JournalEntry::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::with(['company', 'details.companyAccount'])->latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'reference_number' => 'required|string|max:255',
            'reference_date' => 'required|date',
            'remarks' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.company_account_id' => 'required|exists:company_accounts,id',
            'details.*.particulars' => 'required|string',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.credit' => 'required|numeric|min:0',
            'details.*.remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $model = $this->modelClass::create([
                'company_id' => $validated['company_id'],
                'reference_number' => $validated['reference_number'],
                'reference_date' => $validated['reference_date'],
                'remarks' => $validated['remarks'],
                'total_debit' => collect($validated['details'])->sum('debit'),
                'total_credit' => collect($validated['details'])->sum('credit'),
            ]);

            // Create details
            foreach ($validated['details'] as $detail) {
                $model->details()->create($detail);
            }

            DB::commit();

            return response()->json([
                'modelData' => $model->load('details.companyAccount'),
                'message' => "{$this->modelName} created successfully.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create journal entry: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['company', 'details.companyAccount'])->findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'reference_number' => 'required|string|max:255',
            'reference_date' => 'required|date',
            'remarks' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.company_account_id' => 'required|exists:company_accounts,id',
            'details.*.particulars' => 'required|string',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.credit' => 'required|numeric|min:0',
            'details.*.remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $model->update([
                'company_id' => $validated['company_id'],
                'reference_number' => $validated['reference_number'],
                'reference_date' => $validated['reference_date'],
                'remarks' => $validated['remarks'],
                'total_debit' => collect($validated['details'])->sum('debit'),
                'total_credit' => collect($validated['details'])->sum('credit'),
            ]);

            // Delete existing details and create new ones
            $model->details()->delete();
            foreach ($validated['details'] as $detail) {
                $model->details()->create($detail);
            }

            DB::commit();

            return response()->json([
                'modelData' => $model->load('details.companyAccount'),
                'message' => "{$this->modelName} updated successfully.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update journal entry: ' . $e->getMessage()], 500);
        }
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

        $models = $this->modelClass::where('name', 'like', "%{$searchTerm}%")
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
}
