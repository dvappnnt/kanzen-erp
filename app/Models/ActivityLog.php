<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $guarded = [];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the related model (polymorphic relationship).
     */
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo($this->model, 'model_id');
    }

    public function model()
    {
        return $this->morphTo();
    }
}
