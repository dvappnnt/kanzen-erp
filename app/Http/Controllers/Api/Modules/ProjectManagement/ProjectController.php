<?php

namespace App\Http\Controllers\Api\Modules\ProjectManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Project::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        // Join with project_columns and order by column position, then project position
        return $this->modelClass::select('projects.*')
            ->join('project_columns', 'projects.project_column_id', '=', 'project_columns.id')
            ->orderBy('project_columns.position')
            ->orderBy('projects.position')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'created_by_user_id' => 'nullable|exists:users,id',
            'project_column_id' => 'required|exists:project_columns,id',
        ]);

        // Get the highest position in the column and add 1
        $maxPosition = $this->modelClass::where('project_column_id', $validated['project_column_id'])
            ->max('position') ?? -1;
        
        $validated['position'] = $maxPosition + 1;
        
        $model = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' created successfully.",
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
            'description' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'created_by_user_id' => 'nullable|exists:users,id',
            'project_column_id' => 'required|exists:project_columns,id',
            'position' => 'sometimes|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // If column changed or position specified, handle reordering
            if ($model->project_column_id != $validated['project_column_id'] || 
                (isset($validated['position']) && $model->position != $validated['position'])) {
                
                // If moving to a new column, add to the end
                if ($model->project_column_id != $validated['project_column_id']) {
                    $maxPosition = $this->modelClass::where('project_column_id', $validated['project_column_id'])
                        ->max('position') ?? -1;
                    $validated['position'] = $maxPosition + 1;
                }
                
                // If position is specified and different, handle reordering
                if (isset($validated['position']) && $model->position != $validated['position']) {
                    // Get all projects in the same column
                    $columnProjects = $this->modelClass::where('project_column_id', $validated['project_column_id'])
                        ->where('id', '!=', $model->id)
                        ->orderBy('position')
                        ->get();
                    
                    // Insert at position and shift others
                    $position = 0;
                    foreach ($columnProjects as $project) {
                        if ($position == $validated['position']) {
                            $position++;
                        }
                        if ($project->position != $position) {
                            $project->update(['position' => $position]);
                        }
                        $position++;
                    }
                }
            }

            $model->update($validated);
            DB::commit();

            // Fetch the updated model with the column position
            $updatedModel = $this->modelClass::select('projects.*', 'project_columns.position as column_position')
                ->join('project_columns', 'projects.project_column_id', '=', 'project_columns.id')
                ->where('projects.id', $model->id)
                ->first();

            return response()->json([
                'modelData' => $updatedModel,
                'message' => "{$this->modelName} '{$model->name}' updated successfully.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => "Error updating {$this->modelName}: " . $e->getMessage()
            ], 500);
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