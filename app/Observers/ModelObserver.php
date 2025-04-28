<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ModelObserver
{
    public function created($model)
    {
        $this->logActivity($model, 'created');
    }

    public function updated($model)
    {
        $this->logActivity($model, 'updated', $model->getChanges());
    }

    public function deleted($model)
    {
        $this->logActivity($model, 'deleted');
    }

    protected function logActivity($model, string $action, array $changes = null)
    {
        ActivityLog::create([
            'action' => $action,
            'model_type' => get_class($model), // Stores the full class name
            'model_id' => $model->id,
            'user_id' => Auth::id() ?: null, // Ensure null if no authenticated user
            'changes' => $changes ? json_encode($changes) : null, // Encode changes or null
        ]);
    }
}
