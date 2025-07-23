<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_request_id',
        'product_id',
        'requested_qty',
    ];

    // Relationships
    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
