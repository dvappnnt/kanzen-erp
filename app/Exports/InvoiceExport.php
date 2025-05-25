<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Invoice::with(['customer', 'company', 'warehouse', 'paymentMethodDetails'])
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
            'Customer',
            'Warehouse',
            'Type',
            'Invoice Date',
            'Due Date',
            'Payment Date',
            'Discount Rate',
            'Discount Amount',
            'Tax Rate',
            'Tax Amount',
            'Shipping Cost',
            'Subtotal',
            'Total Amount',
            'Currency',
            'Status',
            'Notes',
            'Is Credit',
            'Created At',
            'Updated At'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->number,
            $invoice->company->name ?? '-',
            $invoice->customer->name ?? '-',
            $invoice->warehouse->name ?? '-',
            $invoice->type ?? '-',
            $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '-',
            $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '',
            $invoice->payment_date ? \Carbon\Carbon::parse($invoice->payment_date)->format('Y-m-d') : '',
            $invoice->discount_rate ?? '0',
            $invoice->discount_amount ?? '0',
            $invoice->tax_rate ?? '0',
            $invoice->tax_amount ?? '0',
            $invoice->shipping_cost ?? '0',
            $invoice->subtotal ?? '0',
            $invoice->total_amount ?? '0',
            $invoice->currency ?? '-',
            ucfirst($invoice->status),
            $invoice->notes ?? '-',
            $invoice->is_credit ? 'Yes' : 'No',
            $invoice->created_at->format('Y-m-d H:i:s'),
            $invoice->updated_at->format('Y-m-d H:i:s')
        ];
    }
} 