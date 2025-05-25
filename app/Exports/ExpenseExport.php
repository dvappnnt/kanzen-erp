<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $query = Expense::with(['company', 'supplier', 'category'])
        ->whereDate('created_at', '>=', \Carbon\Carbon::parse($this->fromDate)->startOfDay());

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', \Carbon\Carbon::parse($this->toDate)->endOfDay());
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Reference Number',
            'Company',
            'Category',
            'Supplier',
            'Payment Method',
            'Amount',
            'Currency',
            'Description',
            'Expense Date',
            'Receipt Attachment',
            'Created At',
            'Updated At'
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->reference_number ?? '-',
            $expense->company->name ?? '-',
            $expense->category->name ?? '-',
            $expense->supplier->name ?? '-',
            $expense->payment_method ?? '-',
            $expense->amount ?? '0',
            $expense->currency ?? '-',
            $expense->description ?? '-',
            $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : '-',
            $expense->receipt_attachment ?? '-',
            $expense->created_at ? $expense->created_at->format('Y-m-d H:i:s') : '-',
            $expense->updated_at ? $expense->updated_at->format('Y-m-d H:i:s') : '-',
        ];
    }
} 