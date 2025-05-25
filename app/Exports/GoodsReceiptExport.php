<?php

namespace App\Exports;

use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class GoodsReceiptExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fromDate;
    protected $toDate;
    protected $status;

    public function __construct($fromDate, $toDate, $status = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->status = $status;
    }

    public function collection()
    {
        $query = GoodsReceipt::with([
            'company',
            'purchaseOrder',
            'purchaseOrder.supplier',
            'purchaseOrder.company',
            'purchaseOrder.warehouse'
        ])
        ->whereDate('created_at', '>=', \Carbon\Carbon::parse($this->fromDate)->startOfDay());

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', \Carbon\Carbon::parse($this->toDate)->endOfDay());
        }

        if ($this->status && $this->status !== '*') {
            $query->where('status', $this->status);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'GR Number',
            'Company',
            'Warehouse',
            'Supplier',
            'Order Date',
            'Expected Delivery Date',
            'Status',
            'Payment Terms',
            'Shipping Terms',
            'Tax Rate (%)',
            'Tax Amount',
            'Shipping Cost',
            'Subtotal',
            'Total Amount',
            'Notes',
            'Created At',
            'Updated At'
        ];
    }

    public function map($goodsReceipt): array
    {
        return [
            $goodsReceipt->number,
            $goodsReceipt->company->name ?? '-',
            $goodsReceipt->purchaseOrder->warehouse->name ?? '-',
            $goodsReceipt->purchaseOrder->supplier->name ?? '-',
            $goodsReceipt->purchaseOrder->order_date ? \Carbon\Carbon::parse($goodsReceipt->purchaseOrder->order_date)->format('Y-m-d') : '-',
            $goodsReceipt->purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($goodsReceipt->purchaseOrder->expected_delivery_date)->format('Y-m-d') : '-',
            ucfirst($goodsReceipt->status),
            $goodsReceipt->purchaseOrder->payment_terms ?? '-',
            $goodsReceipt->purchaseOrder->shipping_terms ?? '-',
            $goodsReceipt->purchaseOrder->tax_rate ?? '0',
            $goodsReceipt->purchaseOrder->tax_amount ?? '0',
            $goodsReceipt->purchaseOrder->shipping_cost ?? '0',
            $goodsReceipt->purchaseOrder->subtotal ?? '0',
            $goodsReceipt->purchaseOrder->total_amount ?? '0',
            $goodsReceipt->notes ?? '-',
            $goodsReceipt->created_at->format('Y-m-d H:i:s'),
            $goodsReceipt->updated_at->format('Y-m-d H:i:s')
        ];
    }
} 