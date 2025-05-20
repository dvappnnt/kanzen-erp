<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_column_id',
        'position',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'customer_id',
        'created_by_user_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function projectColumn()
    {
        return $this->belongsTo(ProjectColumn::class);
    }

    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function projectTasks()
    {
        return $this->hasMany(ProjectTask::class);
    }
}
