<?php

namespace App\Http\Controllers\Api\Modules\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = MaterialRequest::with(['requestedBy', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->where(DB::raw('LOWER(reference_no)'), 'like', '%' . strtolower($search) . '%')
                ->orWhereHas('warehouse', function ($q) use ($search) {
                    $q->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($search) . '%');
                });
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    public function autocomplete(Request $request)
    {
        if (!$request->filled('search') || strlen($request->input('search')) < 1) {
            return response()->json(['message' => 'Invalid search term.'], 422);
        }

        $searchTerm = strtolower($request->input('search'));

        $models = MaterialRequest::with(['warehouse', 'requestedBy']) // relationships
            ->whereRaw('LOWER(reference_no) LIKE ?', ["%{$searchTerm}%"])
            ->orWhereHas('warehouse', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        if ($models->isEmpty()) {
            return response()->json([
                'message' => 'No Material Requests found.',
            ], 404);
        }

        return response()->json([
            'data' => $models,
            'message' => 'Material Requests retrieved successfully.',
        ]);
    }
    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['warehouse', 'requestedBy']); // Eager load needed relations
        return response()->json($materialRequest);
    }
}
