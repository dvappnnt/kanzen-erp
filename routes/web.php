<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Public\QrController;

use App\Http\Controllers\Modules\ProjectManagement\ProjectController;
use App\Http\Controllers\Modules\ProjectManagement\ProjectTaskController;

use App\Http\Controllers\Modules\CustomerRelationshipManagement\CustomerController;
use App\Http\Controllers\Modules\CustomerRelationshipManagement\AgentController;
use App\Http\Controllers\Modules\CustomerRelationshipManagement\AdvertisementController;

use App\Http\Controllers\Modules\AccountingManagement\ChartOfAccountController;
use App\Http\Controllers\Modules\AccountingManagement\BankController;
use App\Http\Controllers\Modules\AccountingManagement\CompanyAccountController;
use App\Http\Controllers\Modules\AccountingManagement\ExpenseController;
use App\Http\Controllers\Modules\AccountingManagement\JournalEntryController;
use App\Http\Controllers\Modules\AccountingManagement\InvoiceController;
use App\Http\Controllers\Modules\AccountingManagement\SupplierInvoiceController;

use App\Http\Controllers\Modules\WarehouseManagement\AttributeController;
use App\Http\Controllers\Modules\WarehouseManagement\AttributeValueController;
use App\Http\Controllers\Modules\WarehouseManagement\PosController;
use App\Http\Controllers\Modules\WarehouseManagement\SupplierController;
use App\Http\Controllers\Modules\WarehouseManagement\SupplierProductController;
use App\Http\Controllers\Modules\WarehouseManagement\ProductController;
use App\Http\Controllers\Modules\WarehouseManagement\WarehouseController;
use App\Http\Controllers\Modules\WarehouseManagement\PurchaseOrderController;
use App\Http\Controllers\Modules\WarehouseManagement\GoodsReceiptController;
use App\Http\Controllers\Modules\WarehouseManagement\PurchaseRequisitionController;
use App\Http\Controllers\Modules\WarehouseManagement\ShipmentController;
use App\Http\Controllers\Modules\WarehouseManagement\CourierController;
use App\Http\Controllers\Modules\WarehouseManagement\WarehouseStockTransferController;

use App\Http\Controllers\Modules\HumanResourceManagement\EmployeeController;
use App\Http\Controllers\Modules\HumanResourceManagement\DepartmentController;
use App\Http\Controllers\Modules\HumanResourceManagement\HolidayController;
use App\Http\Controllers\Modules\HumanResourceManagement\PositionController;
use App\Http\Controllers\Modules\HumanResourceManagement\OffenseTypeController;
use App\Http\Controllers\Modules\HumanResourceManagement\EmployeeLeaveController;
use App\Http\Controllers\Modules\HumanResourceManagement\EmployeeOvertimeController;
use App\Http\Controllers\Modules\HumanResourceManagement\DeductionController;
use App\Http\Controllers\Modules\HumanResourceManagement\DocumentTypeController;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->name('pos');

    Route::resource('users', UserController::class)->only(['index', 'show', 'edit', 'create']);
    Route::post('/users/{user}/send-reset-password', [UserController::class, 'sendResetPasswordLink'])
        ->name('users.send-reset-password');

    Route::post('/users/{user}/resend-verification-link', [UserController::class, 'resendVerificationLink'])
        ->name('users.resend-verification-link');

    Route::get('/users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.change-password');

    Route::resource('attributes', AttributeController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('attribute-values', AttributeValueController::class)->only(['index', 'show', 'edit', 'create']);

    Route::group(['prefix' => 'chart-of-accounts'], function () {
        Route::get('/', [ChartOfAccountController::class, 'index'])->name('chart-of-accounts.index');
        Route::get('/create', [ChartOfAccountController::class, 'create'])->name('chart-of-accounts.create');
        Route::get('/{account}/edit', [ChartOfAccountController::class, 'edit'])->name('chart-of-accounts.edit');
    });

    Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');

    Route::get('supplier-invoices/export', [SupplierInvoiceController::class, 'export'])->name('supplier-invoices.export');
    Route::resource('supplier-invoices', SupplierInvoiceController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('supplier-invoices/{supplierInvoice}/print', [SupplierInvoiceController::class, 'print'])->name('supplier-invoices.print');
    
    Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'show', 'edit', 'create']);

    Route::resource('banks', BankController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('company-accounts', CompanyAccountController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('journal-entries', JournalEntryController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('journal-entries/export', [JournalEntryController::class, 'export'])->name('journal-entries.export');

    Route::resource('companies', CompanyController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('agents', AgentController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('shipments', ShipmentController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('couriers', CourierController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('warehouse-stock-transfers', WarehouseStockTransferController::class)->only(['index', 'show', 'edit', 'create']);

    Route::get('categories/export', [CategoryController::class, 'export'])->name('categories.export');
    Route::get('categories/import', [CategoryController::class, 'import'])->name('categories.import');
    Route::resource('categories', CategoryController::class)->only(['index', 'show', 'edit', 'create']);

    Route::get('purchase-orders/export', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show', 'edit', 'create']);
    
    Route::resource('projects', ProjectController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('tasks', ProjectTaskController::class)->only(['index', 'show', 'edit', 'create']);

    Route::get('goods-receipts/export', [GoodsReceiptController::class, 'export'])->name('goods-receipts.export');
    Route::resource('goods-receipts', GoodsReceiptController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('goods-receipts/{goodsReceipt}/print', [GoodsReceiptController::class, 'print'])->name('goods-receipts.print');
    Route::get('goods-receipts/{goodsReceipt}/print/serials', [GoodsReceiptController::class, 'printSerials'])->name('goods-receipts.print.serials');

    Route::resource('purchase-requisitions', PurchaseRequisitionController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');

    Route::resource('suppliers', SupplierController::class)->only(['index', 'show', 'edit', 'create']);
    Route::prefix('suppliers/{supplier}')->group(function () {
        Route::get('products', [SupplierController::class, 'products'])->name('suppliers.products');
    });

    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class)->only(['index', 'show', 'edit', 'create']);
    Route::prefix('products/{product}')->group(function () {
        Route::get('specifications', [ProductController::class, 'specifications'])->name('products.specifications');
        Route::get('variations', [ProductController::class, 'variations'])->name('products.variations');
        Route::get('images', [ProductController::class, 'images'])->name('products.images');
    });

    Route::resource('document-types', DocumentTypeController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('deductions', DeductionController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('holidays', HolidayController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('positions', PositionController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('offense-types', OffenseTypeController::class)->only(['index', 'show', 'edit', 'create']);

    Route::resource('employee-leaves', EmployeeLeaveController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('employee-overtimes', EmployeeOvertimeController::class)->only(['index', 'show', 'edit', 'create']);

    Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('employees/{employee}/print', [EmployeeController::class, 'print'])->name('employees.print');
    Route::prefix('employees/{employee}')->group(function () {
        Route::get('educational-attainments', [EmployeeController::class, 'educationalAttainments'])->name('employees.educational-attainments');
        Route::get('work-experiences', [EmployeeController::class, 'workExperiences'])->name('employees.work-experiences');
        Route::get('dependents', [EmployeeController::class, 'dependents'])->name('employees.dependents');
        Route::get('awards', [EmployeeController::class, 'awards'])->name('employees.awards');
        Route::get('contact-details', [EmployeeController::class, 'contactDetails'])->name('employees.contact-details');
        Route::get('documents', [EmployeeController::class, 'documents'])->name('employees.documents');
        Route::get('certificates', [EmployeeController::class, 'certificates'])->name('employees.certificates');
        Route::get('disciplinary-actions', [EmployeeController::class, 'disciplinaryActions'])->name('employees.disciplinary-actions');
        Route::get('payroll-details', [EmployeeController::class, 'payrollDetails'])->name('employees.payroll-details');
        Route::get('employment-details', [EmployeeController::class, 'employmentDetails'])->name('employees.employment-details');
        Route::get('skills', [EmployeeController::class, 'skills'])->name('employees.skills');
        Route::get('performance-reviews', [EmployeeController::class, 'performanceReviews'])->name('employees.performance-reviews');
    });

    Route::resource('departments', DepartmentController::class)->only(['index', 'show', 'edit', 'create']);

    Route::get('app/settings', [SettingController::class, 'index'])->name('app.settings.index');
    Route::get('app/settings/style-kit', [SettingController::class, 'styleKit'])->name('app.settings.style-kit');
    Route::get('app/settings/typography', [SettingController::class, 'typography'])->name('app.settings.typography');
    Route::get('app/settings/environment', [SettingController::class, 'environment'])->name('app.settings.environment');
    Route::get('app/settings/database', [SettingController::class, 'database'])->name('app.settings.database');
    Route::resource('/activity-logs', ActivityLogController::class)->only(['index', 'show']);

    Route::resource('roles', RoleController::class)->except('show');
});

Route::group(['prefix' => 'public'], function () {
    Route::group(['prefix' => 'qr'], function () {
        Route::get('/warehouse-products/{product}', [QrController::class, 'warehouseProducts'])->name('qr.warehouse-products');
        Route::get('/products/{product}', [QrController::class, 'products'])->name('qr.products');
        Route::get('/suppliers/{supplier}', [QrController::class, 'suppliers'])->name('qr.suppliers');
        Route::get('/warehouses/{warehouse}', [QrController::class, 'warehouses'])->name('qr.warehouses');
        Route::get('/companies/{company}', [QrController::class, 'companies'])->name('qr.companies');
        Route::get('/employees/{employee}', [QrController::class, 'employees'])->name('qr.employees');
        Route::get('/purchase-orders/{purchaseOrder}', [QrController::class, 'purchaseOrders'])->name('qr.purchase-orders');
        Route::get('/goods-receipts/{goodsReceipt}', [QrController::class, 'goodsReceipts'])->name('qr.goods-receipts');
        Route::get('/purchase-requisitions/{purchaseRequisition}', [QrController::class, 'purchaseRequisitions'])->name('qr.purchase-requisitions');
    });
});

Route::get('/graphql-playground', fn() => view('vendor.graphiql.index', [
    'url' => '/graphql',
    'subscriptionUrl' => null, // or your WebSocket URL if using subscriptions
]));
