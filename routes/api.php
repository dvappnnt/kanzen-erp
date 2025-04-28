<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;

use App\Http\Controllers\Api\Modules\AccountingManagement\BankController;
use App\Http\Controllers\Api\Modules\AccountingManagement\CompanyAccountController;
use App\Http\Controllers\Api\Modules\AccountingManagement\ExpenseController;
use App\Http\Controllers\Api\Modules\AccountingManagement\JournalEntryController;

use App\Http\Controllers\Api\Modules\WarehouseManagement\SupplierController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\PurchaseOrderController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\GoodsReceiptController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\PurchaseRequisitionController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\SupplierProductController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ProductVariationAttributeController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\AttributeController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\AttributeValueController;

use App\Http\Controllers\Api\Modules\WarehouseManagement\ProductController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ProductSpecificationController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ProductVariationController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ProductImageController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::get('/all/countries', [CountryController::class, 'all'])->name('api.countries.all');
Route::resource('countries', CountryController::class)->only(['index', 'show', 'destroy']);

Route::as('api.')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('autocomplete/users', [UserController::class, 'autocomplete'])->name('users.autocomplete');
    Route::put('/users/update-password/{user}', [UserController::class, 'updatePassword'])->name('users.update-password');

    Route::apiResource('customers', CustomerController::class);
    Route::get('autocomplete/customers', [CustomerController::class, 'autocomplete'])->name('customers.autocomplete');

    Route::apiResource('expenses', ExpenseController::class);
    Route::get('autocomplete/expenses', [ExpenseController::class, 'autocomplete'])->name('expenses.autocomplete');

    Route::apiResource('banks', BankController::class);
    Route::get('autocomplete/banks', [BankController::class, 'autocomplete'])->name('banks.autocomplete');

    Route::apiResource('company-accounts', CompanyAccountController::class);
    Route::get('autocomplete/company-accounts', [CompanyAccountController::class, 'autocomplete'])->name('company-accounts.autocomplete');

    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::get('autocomplete/journal-entries', [JournalEntryController::class, 'autocomplete'])->name('journal-entries.autocomplete');

    Route::apiResource('companies', CompanyController::class);
    Route::get('autocomplete/companies', [CompanyController::class, 'autocomplete'])->name('companies.autocomplete');

    Route::apiResource('attributes', AttributeController::class);
    Route::get('autocomplete/attributes', [AttributeController::class, 'autocomplete'])->name('attributes.autocomplete');

    Route::apiResource('suppliers', SupplierController::class);
    Route::get('autocomplete/suppliers', [SupplierController::class, 'autocomplete'])->name('suppliers.autocomplete');
    Route::get('suppliers/{supplier}/products', [SupplierController::class, 'products'])->name('suppliers.products');

    Route::group(['prefix' => 'suppliers/{supplier}'], function () {
        Route::apiResource('products', SupplierProductController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    Route::apiResource('attributes', AttributeController::class);
    Route::get('autocomplete/attributes', [AttributeController::class, 'autocomplete'])->name('attributes.autocomplete');

    Route::apiResource('attribute-values', AttributeValueController::class);
    Route::get('autocomplete/attribute-values', [AttributeValueController::class, 'autocomplete'])->name('attribute-values.autocomplete');

    Route::apiResource('products', ProductController::class);
    Route::get('autocomplete/products', [ProductController::class, 'autocomplete'])->name('products.autocomplete');

    Route::apiResource('product-specifications', ProductSpecificationController::class);
    Route::get('autocomplete/product-specifications', [ProductSpecificationController::class, 'autocomplete'])->name('product-specifications.autocomplete');

    Route::group(['prefix' => 'products/{product}'], function () {
        Route::apiResource('variations', ProductVariationController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    Route::apiResource('product-images', ProductImageController::class);
    Route::get('autocomplete/product-images', [ProductImageController::class, 'autocomplete'])->name('product-images.autocomplete');

    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('autocomplete/warehouses', [WarehouseController::class, 'autocomplete'])->name('warehouses.autocomplete');

    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::get('autocomplete/purchase-orders', [PurchaseOrderController::class, 'autocomplete'])->name('purchase-orders.autocomplete');

    Route::apiResource('goods-receipts', GoodsReceiptController::class);
    Route::get('autocomplete/goods-receipts', [GoodsReceiptController::class, 'autocomplete'])->name('goods-receipts.autocomplete');

    Route::apiResource('roles', RoleController::class);
    Route::get('autocomplete/roles', [RoleController::class, 'autocomplete'])->name('roles.autocomplete');

    Route::apiResource('categories', CategoryController::class);
    Route::get('autocomplete/categories', [CategoryController::class, 'autocomplete'])->name('categories.autocomplete');

    Route::post('app-settings/schedule', [SettingController::class, 'updateSchedule']);
    Route::get('app-settings/export-database', [SettingController::class, 'exportDatabase']);
    Route::post('app-settings/import-database', [SettingController::class, 'importDatabase']);
    Route::put('app-settings/environment/update', [SettingController::class, 'environment'])->name('app.settings.environment.update');
    Route::put('app-settings/style/update', [SettingController::class, 'style'])->name('app.settings.style.update');
    Route::apiResource('app-settings', SettingController::class)->only(['show', 'update']);

    Route::apiResource('activity-logs', ActivityLogController::class);

    Route::get('/api/notifications', function () {
        return auth()->user()->unreadNotifications;
    });

    Route::get('/notifications', function () {
        $user = auth()->user();
        return response()->json([
            'notifications' => $user->notifications,
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    });

    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    });

    Route::post('/notifications/mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });

    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::get('autocomplete/purchase-requisitions', [PurchaseRequisitionController::class, 'autocomplete'])->name('purchase-requisitions.autocomplete');

    Route::apiResource('product-variation-attributes', ProductVariationAttributeController::class);
    Route::post('product-variation-attributes/{id}/restore', [ProductVariationAttributeController::class, 'restore'])->name('product-variation-attributes.restore');
});
