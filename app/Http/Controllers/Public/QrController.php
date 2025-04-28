<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QrController extends Controller
{
    protected $modelClass;
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelClass = \App\Models\Product::class;
        $this->modelName = Str::plural(Str::singular(class_basename($this->modelClass)));
        $this->modulePath = 'Public/Qr/';
    }

    public function show($id)
    {
        $model = $this->modelClass::with(['category'])->findOrFail($id);

        return Inertia::render("{$this->modulePath}/{$this->modelName}", [
            'modelData' => $model,
        ]);
    }
}
