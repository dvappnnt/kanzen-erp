<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'requested_by_user_id',
        'warehouse_id',
        'status',
        'remarks',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
