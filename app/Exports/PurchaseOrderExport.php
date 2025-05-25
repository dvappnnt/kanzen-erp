<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class PurchaseOrderExport implements FromCollection, WithHeadings, WithMapping
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
        $query = PurchaseOrder::with(['company', 'warehouse', 'supplier'])
            ->whereDate('created_at', '>=', Carbon::parse($this->fromDate)->startOfDay());

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->toDate)->endOfDay());
        }

        if ($this->status && $this->status !== '*') {
            $query->where('status', $this->status);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'PO Number',
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

    public function map($purchaseOrder): array
    {
        return [
            $purchaseOrder->number,
            $purchaseOrder->company->name ?? '-',
            $purchaseOrder->warehouse->name ?? '-',
            $purchaseOrder->supplier->name ?? '-',
            $purchaseOrder->order_date ? Carbon::parse($purchaseOrder->order_date)->format('Y-m-d') : '-',
            $purchaseOrder->expected_delivery_date ? Carbon::parse($purchaseOrder->expected_delivery_date)->format('Y-m-d') : '-',
            ucfirst($purchaseOrder->status),
            $purchaseOrder->payment_terms ?? '-',
            $purchaseOrder->shipping_terms ?? '-',
            $purchaseOrder->tax_rate ?? '0',
            $purchaseOrder->tax_amount ?? '0',
            $purchaseOrder->shipping_cost ?? '0',
            $purchaseOrder->subtotal ?? '0',
            $purchaseOrder->total_amount ?? '0',
            $purchaseOrder->notes ?? '-',
            $purchaseOrder->created_at->format('Y-m-d H:i:s'),
            $purchaseOrder->updated_at->format('Y-m-d H:i:s')
        ];
    }
} 