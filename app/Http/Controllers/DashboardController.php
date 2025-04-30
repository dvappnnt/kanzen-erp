<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $isSuperAdmin = $user->hasRole('super-admin');
        $companyId = $isSuperAdmin ? null : $user->company->id;

        // Get total receivables (invoices)
        $receivablesQuery = Invoice::query();
        if (!$isSuperAdmin) {
            $receivablesQuery->where('company_id', $companyId);
        }
        $totalReceivables = $receivablesQuery->where('status', '!=', 'paid')->sum('total');

        // Get total payables (expenses)
        $payablesQuery = Expense::query();
        if (!$isSuperAdmin) {
            $payablesQuery->where('company_id', $companyId);
        }
        $totalPayables = $payablesQuery->sum('amount');

        // Get counts
        $customersCount = $isSuperAdmin
            ? Customer::count()
            : Customer::where('company_id', $companyId)->count();

        $warehousesCount = $isSuperAdmin
            ? Warehouse::count()
            : Warehouse::where('company_id', $companyId)->count();

        // Get cashflow data for the current year
        $cashflowData = $this->getCashflowData($companyId, $isSuperAdmin);

        return Inertia::render('Dashboard', [
            'stats' => [
                'receivables' => $totalReceivables,
                'payables' => $totalPayables,
                'customers_count' => $customersCount,
                'warehouses_count' => $warehousesCount,
                'cashflow' => $cashflowData,
            ]
        ]);
    }

    private function getCashflowData($companyId, $isSuperAdmin)
    {
        $currentYear = now()->year;
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $inflowQuery = Invoice::query();
            if (!$isSuperAdmin) {
                $inflowQuery->where('company_id', $companyId);
            }
            $inflow = $inflowQuery
                ->whereYear('invoice_date', $currentYear)
                ->whereMonth('invoice_date', $month)
                ->sum('total');

            $outflowQuery = Expense::query();
            if (!$isSuperAdmin) {
                $outflowQuery->where('company_id', $companyId);
            }
            $outflow = $outflowQuery
                ->whereYear('expense_date', $currentYear)
                ->whereMonth('expense_date', $month)
                ->sum('amount');

            $months[] = [
                'month' => now()->month($month)->format('M'),
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ];
        }

        return $months;
    }
}
