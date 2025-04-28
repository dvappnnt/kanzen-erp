<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = Country::class;
        $this->modelName = class_basename($this->modelClass);
    }

    /**
     * Display a listing of countries.
     */
    public function all(Request $request)
    {
        $countries = $this->modelClass::query()
            ->get();

        $countries->transform(function ($country) {
            $country->code = strtolower($country->code); // Convert to lowercase
            return $country;
        });

        return response()->json($countries);
    }

    public function index(Request $request)
    {
        $countries = $this->modelClass::query()
            ->paginate(10); // Customize pagination if needed

        $countries->transform(function ($country) {
            $country->code = strtolower($country->code); // Convert to lowercase
            return $country;
        });
        return response()->json($countries);
    }

    /**
     * Display a specific country.
     */
    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);

        return response()->json($model, 200);
    }

    /**
     * Delete a specific country.
     */
    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => "{$this->modelName} '{$model->name}' deleted successfully.",
        ], 200);
    }

    /**
     * Autocomplete search for countries.
     */
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
            'message' => "{$this->modelName}s retrieved successfully.",
        ], 200);
    }

    private function codeToEmoji($code)
    {
        $offset = 127397; // Unicode offset for regional indicators
        return collect(str_split(strtoupper($code)))
            ->map(fn($char) => mb_chr(ord($char) + $offset, 'UTF-8'))
            ->join('');
    }
}
