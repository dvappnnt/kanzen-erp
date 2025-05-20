<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ProjectTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'project_id',
        'created_by_user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function projectTaskMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }
}
