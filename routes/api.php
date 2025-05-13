<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CategoryController;

use App\Http\Controllers\Api\Modules\CustomerRelationshipManagement\CustomerController;
use App\Http\Controllers\Api\Modules\CustomerRelationshipManagement\AgentController;

use App\Http\Controllers\Api\Modules\AccountingManagement\BankController;
use App\Http\Controllers\Api\Modules\AccountingManagement\CompanyAccountController;
use App\Http\Controllers\Api\Modules\AccountingManagement\ExpenseController;
use App\Http\Controllers\Api\Modules\AccountingManagement\JournalEntryController;
use App\Http\Controllers\Api\Modules\AccountingManagement\InvoiceController;
use App\Http\Controllers\Api\Modules\AccountingManagement\SupplierInvoiceController;
use App\Http\Controllers\Api\Modules\AccountingManagement\SupplierInvoicePaymentController;

use App\Http\Controllers\Api\Modules\WarehouseManagement\SupplierController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\PurchaseOrderController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\PurchaseOrderDetailController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\GoodsReceiptController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\GoodsReceiptDetailController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\GoodsReceiptDetailRemarkController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseProductController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseProductSerialController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseTransferController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ApprovalRemarkController;

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
    Route::get('complete/customers', [CustomerController::class, 'complete'])->name('customers.complete');
    Route::get('autocomplete/customers', [CustomerController::class, 'autocomplete'])->name('customers.autocomplete');

    Route::apiResource('agents', AgentController::class);
    Route::get('autocomplete/agents', [AgentController::class, 'autocomplete'])->name('agents.autocomplete');

    Route::apiResource('expenses', ExpenseController::class);
    Route::get('autocomplete/expenses', [ExpenseController::class, 'autocomplete'])->name('expenses.autocomplete');

    Route::apiResource('banks', BankController::class);
    Route::get('autocomplete/banks', [BankController::class, 'autocomplete'])->name('banks.autocomplete');

    Route::apiResource('invoices', InvoiceController::class);
    Route::get('autocomplete/invoices', [InvoiceController::class, 'autocomplete'])->name('invoices.autocomplete');

    Route::apiResource('supplier-invoices', SupplierInvoiceController::class);
    Route::get('autocomplete/supplier-invoices', [SupplierInvoiceController::class, 'autocomplete'])->name('supplier-invoices.autocomplete');

    Route::apiResource('supplier-invoice-payments', SupplierInvoicePaymentController::class);
    Route::get('autocomplete/supplier-invoice-payments', [SupplierInvoicePaymentController::class, 'autocomplete'])->name('supplier-invoice-payments.autocomplete');
    Route::post('supplier-invoice-payments/{supplierInvoicePayment}/approve', [SupplierInvoicePaymentController::class, 'approve'])->name('supplier-invoice-payments.approve');
    Route::post('supplier-invoice-payments/{supplierInvoicePayment}/reject', [SupplierInvoicePaymentController::class, 'reject'])->name('supplier-invoice-payments.reject');

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

    Route::group(['prefix' => 'suppliers/{supplier}'], function () {
        Route::apiResource('products', SupplierProductController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::put('products/{product}/details/{detail}', [SupplierProductController::class, 'updateDetail']);
    });

    Route::apiResource('attributes', AttributeController::class);
    Route::get('autocomplete/attributes', [AttributeController::class, 'autocomplete'])->name('attributes.autocomplete');

    Route::apiResource('attribute-values', AttributeValueController::class);
    Route::get('autocomplete/attribute-values', [AttributeValueController::class, 'autocomplete'])->name('attribute-values.autocomplete');

    Route::apiResource('products', ProductController::class);
    Route::get('complete/products', [ProductController::class, 'complete'])->name('products.complete');
    Route::get('autocomplete/products', [ProductController::class, 'autocomplete'])->name('products.autocomplete');

    Route::apiResource('product-specifications', ProductSpecificationController::class);
    Route::get('autocomplete/product-specifications', [ProductSpecificationController::class, 'autocomplete'])->name('product-specifications.autocomplete');

    Route::group(['prefix' => 'products/{product}', 'as' => 'products.'], function () {
        Route::apiResource('variations', ProductVariationController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    Route::apiResource('product-images', ProductImageController::class);
    Route::get('autocomplete/product-images', [ProductImageController::class, 'autocomplete'])->name('product-images.autocomplete');

    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('complete/warehouses', [WarehouseController::class, 'complete'])->name('warehouses.complete');
    Route::get('autocomplete/warehouses', [WarehouseController::class, 'autocomplete'])->name('warehouses.autocomplete');
    Route::get('warehouses/{warehouse}/products', [WarehouseController::class, 'products'])->name('warehouses.products');

    Route::apiResource('warehouse-products', WarehouseProductController::class);
    Route::get('autocomplete/warehouse-products', [WarehouseProductController::class, 'autocomplete'])->name('warehouse-products.autocomplete');
    Route::get('search/warehouse-products', [WarehouseProductController::class, 'search'])->name('warehouse-products.search');
    Route::get('serial-check/warehouse-products', [WarehouseProductController::class, 'serialCheck'])->name('warehouse-products.serial-check');

    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/pending', [PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/order', [PurchaseOrderController::class, 'order'])->name('purchase-orders.order');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::get('autocomplete/purchase-orders', [PurchaseOrderController::class, 'autocomplete'])->name('purchase-orders.autocomplete');
    
    // Add nested route for purchase order details
    Route::group(['prefix' => 'purchase-orders/{purchaseOrder}'], function () {
        Route::apiResource('details', PurchaseOrderDetailController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    Route::apiResource('approval-remarks', ApprovalRemarkController::class);

    Route::apiResource('purchase-order-details', PurchaseOrderDetailController::class);
    Route::get('autocomplete/purchase-order-details', [PurchaseOrderDetailController::class, 'autocomplete'])->name('purchase-order-details.autocomplete');

    Route::apiResource('goods-receipt-detail-remarks', GoodsReceiptDetailRemarkController::class);

    Route::apiResource('goods-receipts', GoodsReceiptController::class);
    Route::get('autocomplete/goods-receipts', [GoodsReceiptController::class, 'autocomplete'])->name('goods-receipts.autocomplete');
    Route::post('goods-receipts/{goodsReceipt}/transfer', [GoodsReceiptController::class, 'transfer'])->name('goods-receipts.transfer');

    Route::apiResource('goods-receipt-details', GoodsReceiptDetailController::class);
    Route::get('autocomplete/goods-receipt-details', [GoodsReceiptDetailController::class, 'autocomplete'])->name('goods-receipt-details.autocomplete');
    Route::post('goods-receipt-details/{goodsReceiptDetail}/receive', [GoodsReceiptDetailController::class, 'receive'])->name('goods-receipt-details.receive');
    Route::post('goods-receipt-details/{goodsReceiptDetail}/return', [GoodsReceiptDetailController::class, 'return'])->name('goods-receipt-details.return');

    // Add route for updating serials
    Route::put('goods-receipt-serials/{serial}', [GoodsReceiptDetailController::class, 'updateSerial'])->name('goods-receipt-serials.update');

    // Add route for deleting serials
    Route::delete('goods-receipt-serials/{serial}', [GoodsReceiptDetailController::class, 'deleteSerial'])->name('goods-receipt-serials.delete');

    Route::apiResource('warehouse-product-serials', WarehouseProductSerialController::class);
    Route::get('autocomplete/warehouse-product-serials', [WarehouseProductSerialController::class, 'autocomplete'])->name('warehouse-product-serials.autocomplete');

    Route::apiResource('warehouse-transfers', WarehouseTransferController::class);
    Route::get('autocomplete/warehouse-transfers', [WarehouseTransferController::class, 'autocomplete'])->name('warehouse-transfers.autocomplete');

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
        return Auth::user()->unreadNotifications;
    });

    Route::get('/notifications', function () {
        $user = Auth::user();
        return response()->json([
            'notifications' => $user->notifications,
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    });

    Route::post('/notifications/{id}/read', function ($id) {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    });

    Route::post('/notifications/mark-all-read', function () {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });

    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::get('autocomplete/purchase-requisitions', [PurchaseRequisitionController::class, 'autocomplete'])->name('purchase-requisitions.autocomplete');

    Route::apiResource('product-variation-attributes', ProductVariationAttributeController::class);
    Route::post('product-variation-attributes/{id}/restore', [ProductVariationAttributeController::class, 'restore'])->name('product-variation-attributes.restore');
});
