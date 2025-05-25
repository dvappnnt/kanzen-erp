<?php

namespace App\Exports;

use App\Models\SupplierInvoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SupplierInvoiceExport implements FromCollection, WithHeadings, WithMapping
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
        $query = SupplierInvoice::with(['supplier', 'purchaseOrder'])
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
            'Invoice Number',
            'Company',
            'Supplier',
            'Goods Receipt',
            'Purchase Order',
            'Invoice Date',
            'Due Date',
            'Tax Rate (%)',
            'Tax Amount',
            'Shipping Cost',
            'Subtotal',
            'Total Amount',
            'Status',
            'Remarks',
            'Created At',
            'Updated At'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->company->name ?? '-',
            $invoice->supplier->name ?? '-',
            $invoice->goodsReceipt->number ?? '-',
            $invoice->purchaseOrder->number ?? '-',
            $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '-',
            $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '',
            $invoice->tax_rate ?? '0',
            $invoice->tax_amount ?? '0',
            $invoice->shipping_cost ?? '0',
            $invoice->subtotal ?? '0',
            $invoice->total_amount ?? '0',
            ucfirst($invoice->status),
            $invoice->remarks ?? '-',
            $invoice->created_at->format('Y-m-d H:i:s'),
            $invoice->updated_at->format('Y-m-d H:i:s')
        ];
    }
} 