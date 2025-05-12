<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_account_id',
        'number',
        'company_id',
        'warehouse_id',
        'supplier_id',
        'purchase_requisition_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'payment_terms',
        'shipping_terms',
        'notes',
        'tax_rate',
        'tax_amount',
        'shipping_cost',
        'subtotal',
        'total_amount',
        'created_by_user_id',
        'approved_by',
        'approved_at',
        'approval_level',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->number;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected static function booted()
    {
        static::creating(function ($modelData) {
            $modelData->created_by_user_id = Auth::id();

            if (empty($modelData->number)) {
                $company = Company::find($modelData->company_id);

                if ($company) {
                    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3));
                    $count = self::where('company_id', $modelData->company_id)->withTrashed()->count() + 1;
                    $modelData->number = sprintf('%s-PO-%06d', $prefix, $count);
                } else {
                    $modelData->number = 'UNK-PO-' . sprintf('%06d', rand(1, 999999));
                }
            }

            // Set initial status if not set
            if (empty($modelData->status)) {
                $modelData->status = 'draft';
            }
        });

        static::created(function ($purchaseOrder) {
            $approvalLevelSettings = ApprovalLevelSetting::where('type', 'purchase-order')->where('company_id', $purchaseOrder->company_id)->get();
            foreach($approvalLevelSettings as $approvalLevelSetting) {
                ApprovalLevel::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'level' => $approvalLevelSetting->level,
                    'label' => $approvalLevelSetting->label,
                    'user_id' => $approvalLevelSetting->user_id
                ]);
            }
        });

        static::updated(function ($purchaseOrder) {
            // Check if status was changed to 'ordered'
            if ($purchaseOrder->status === 'ordered' && $purchaseOrder->wasChanged('status')) {
                // Check if a goods receipt already exists
                if (!$purchaseOrder->goodsReceipts()->exists()) {
                    DB::transaction(function () use ($purchaseOrder) {
                        // Create the goods receipt
                        $company = Company::find($purchaseOrder->company_id);
                        $prefix = $company ? strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 3)) : 'UNK';
                        $count = GoodsReceipt::where('company_id', $purchaseOrder->company_id)->withTrashed()->count() + 1;
                        $number = sprintf('%s-GR-%06d', $prefix, $count);

                        $goodsReceipt = GoodsReceipt::create([
                            'company_id' => $purchaseOrder->company_id,
                            'purchase_order_id' => $purchaseOrder->id,
                            'number' => $number,
                            'date' => now(),
                            'status' => 'pending',
                            'notes' => "Auto-generated from PO: {$purchaseOrder->number}",
                            'created_by_user_id' => Auth::id()
                        ]);

                        // Create goods receipt details for each purchase order detail
                        foreach ($purchaseOrder->details as $poDetail) {
                            GoodsReceiptDetail::create([
                                'goods_receipt_id' => $goodsReceipt->id,
                                'purchase_order_detail_id' => $poDetail->id,
                                'expected_qty' => $poDetail->qty,
                                'received_qty' => 0,
                                'notes' => null
                            ]);
                        }
                    });
                }
            }
        });
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function approvalRemarks()
    {
        return $this->hasMany(ApprovalRemark::class);
    }
}
