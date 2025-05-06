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

use App\Http\Controllers\Modules\CustomerRelationshipManagement\CustomerController;
use App\Http\Controllers\Modules\CustomerRelationshipManagement\AgentController;

use App\Http\Controllers\Modules\AccountingManagement\BankController;
use App\Http\Controllers\Modules\AccountingManagement\CompanyAccountController;
use App\Http\Controllers\Modules\AccountingManagement\ExpenseController;
use App\Http\Controllers\Modules\AccountingManagement\JournalEntryController;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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

    Route::resource('expenses', ExpenseController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('banks', BankController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('company-accounts', CompanyAccountController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('journal-entries', JournalEntryController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('companies', CompanyController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('categories', CategoryController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('agents', AgentController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'show', 'edit', 'create']);
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show', 'edit', 'create']);
    
    Route::resource('goods-receipts', GoodsReceiptController::class)->only(['index', 'show', 'edit', 'create']);

    Route::resource('purchase-requisitions', PurchaseRequisitionController::class)->only(['index', 'show', 'edit', 'create']);
    Route::get('purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');

    Route::resource('suppliers', SupplierController::class)->only(['index', 'show', 'edit', 'create']);
    Route::prefix('suppliers/{supplier}')->group(function () {
        Route::get('products', [SupplierController::class, 'products'])->name('suppliers.products');
    });

    Route::resource('products', ProductController::class)->only(['index', 'show', 'edit', 'create']);
    Route::prefix('products/{product}')->group(function () {
        Route::get('specifications', [ProductController::class, 'specifications'])->name('products.specifications');
        Route::get('variations', [ProductController::class, 'variations'])->name('products.variations');
        Route::get('images', [ProductController::class, 'images'])->name('products.images');
    });

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
        Route::get('/purchase-orders/{purchaseOrder}', [QrController::class, 'purchaseOrders'])->name('qr.purchase-orders');
        Route::get('/goods-receipts/{goodsReceipt}', [QrController::class, 'goodsReceipts'])->name('qr.goods-receipts');
        Route::get('/purchase-requisitions/{purchaseRequisition}', [QrController::class, 'purchaseRequisitions'])->name('qr.purchase-requisitions');
    });
});

Route::get('/graphql-playground', fn() => view('vendor.graphiql.index', [
    'url' => '/graphql',
    'subscriptionUrl' => null, // or your WebSocket URL if using subscriptions
]));
