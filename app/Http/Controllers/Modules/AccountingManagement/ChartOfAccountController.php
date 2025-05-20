<?php

namespace App\Http\Controllers\Modules\AccountingManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\Account;

class ChartOfAccountController extends Controller
{
    protected $modelName;
    protected $modulePath;

    public function __construct()
    {
        $this->modelName = 'ChartOfAccounts';
        $this->modulePath = 'Modules/AccountingManagement';
    }

    public function index()
    {
        $accountTypes = AccountType::with(['accounts' => function($query) {
            $query->orderBy('code', 'asc')
                  ->withSum('expenses', 'amount');
        }])->orderBy('code', 'asc')->get();

        // Calculate type totals
        $accountTypes = $accountTypes->map(function($type) {
            $type->total = $type->accounts->sum('expenses_sum_amount') ?? 0;
            return $type;
        });
        
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Index", [
            'accountTypes' => $accountTypes,
        ]);
    }

    public function create()
    {
        $accountTypes = AccountType::orderBy('code', 'asc')->get();
        
        return Inertia::render("{$this->modulePath}/{$this->modelName}/Create", [
            'accountTypes' => $accountTypes,
        ]);
    }

    public function edit($id)
    {
        $account = Account::with('type')->findOrFail($id);
        $accountTypes = AccountType::orderBy('code', 'asc')->get();

        return Inertia::render("{$this->modulePath}/{$this->modelName}/Edit", [
            'account' => $account,
            'accountTypes' => $accountTypes,
        ]);
    }
}
