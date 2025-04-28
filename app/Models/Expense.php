<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'company_id',
        'reference_number',
        'category_id',
        'payee',
        'payment_method',
        'amount',
        'currency',
        'description',
        'expense_date',
        'created_by_user_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
