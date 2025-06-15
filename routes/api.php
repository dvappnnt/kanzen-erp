<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CategoryController;

use App\Http\Controllers\Api\Modules\ProjectManagement\ProjectController;
use App\Http\Controllers\Api\Modules\ProjectManagement\ProjectTaskController;
use App\Http\Controllers\Api\Modules\ProjectManagement\ProjectColumnController;

use App\Http\Controllers\Api\Modules\CustomerRelationshipManagement\CustomerController;
use App\Http\Controllers\Api\Modules\CustomerRelationshipManagement\AgentController;
use App\Http\Controllers\Api\Modules\CustomerRelationshipManagement\AdvertisementController;

use App\Http\Controllers\Api\Modules\AccountingManagement\BankController;
use App\Http\Controllers\Api\Modules\AccountingManagement\CompanyAccountController;
use App\Http\Controllers\Api\Modules\AccountingManagement\ExpenseController;
use App\Http\Controllers\Api\Modules\AccountingManagement\JournalEntryController;
use App\Http\Controllers\Api\Modules\AccountingManagement\InvoiceController;
use App\Http\Controllers\Api\Modules\AccountingManagement\InvoicePaymentMethodDetailController;
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
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseStockAdjustmentController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseStockTransferController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseStockTransferDetailController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\ShipmentController;
use App\Http\Controllers\Api\Modules\WarehouseManagement\CourierController;

use App\Http\Controllers\Api\Modules\AccountingManagement\AccountController;
use App\Http\Controllers\Api\Modules\AccountingManagement\AccountTypeController;

use App\Http\Controllers\Api\Modules\HumanResourceManagement\DocumentTypeController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\OffenseTypeController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\DepartmentController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\PositionController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\HolidayController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\DeductionController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeEducationalAttainmentController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeWorkExperienceController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeContactDetailController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeDependentController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeDisciplinaryActionController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeCertificateController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeDocumentController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeAwardController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeAttendanceDetailController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeSkillController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeePerformanceReviewController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeLeaveController;
use App\Http\Controllers\Api\Modules\HumanResourceManagement\EmployeeOvertimeController;

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

    Route::apiResource('advertisements', AdvertisementController::class);
    Route::get('complete/advertisements', [AdvertisementController::class, 'complete'])->name('advertisements.complete');
    Route::get('autocomplete/advertisements', [AdvertisementController::class, 'autocomplete'])->name('advertisements.autocomplete');

    Route::apiResource('agents', AgentController::class);
    Route::get('autocomplete/agents', [AgentController::class, 'autocomplete'])->name('agents.autocomplete');

    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::get('autocomplete/expenses', [ExpenseController::class, 'autocomplete'])->name('expenses.autocomplete');

    Route::apiResource('banks', BankController::class);
    Route::get('autocomplete/banks', [BankController::class, 'autocomplete'])->name('banks.autocomplete');

    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('autocomplete/invoices', [InvoiceController::class, 'autocomplete'])->name('invoices.autocomplete');

    // Add new routes for invoice payments
    Route::post('invoices/{invoice}/payments', [InvoicePaymentMethodDetailController::class, 'store'])->name('invoice-payments.store');
    Route::put('invoices/{invoice}/payments/{payment}', [InvoicePaymentMethodDetailController::class, 'update'])->name('invoice-payments.update');
    Route::delete('invoices/{invoice}/payments/{payment}', [InvoicePaymentMethodDetailController::class, 'destroy'])->name('invoice-payments.destroy');
    Route::post('invoices/{invoice}/payments/{payment}/approve', [InvoicePaymentMethodDetailController::class, 'approve'])->name('invoice-payments.approve');
    Route::post('invoices/{invoice}/payments/{payment}/reject', [InvoicePaymentMethodDetailController::class, 'reject'])->name('invoice-payments.reject');

    Route::apiResource('supplier-invoices', SupplierInvoiceController::class);
    Route::post('supplier-invoices/export', [SupplierInvoiceController::class, 'export'])->name('supplier-invoices.export');
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

    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
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

    Route::apiResource('shipments', ShipmentController::class);
    Route::get('complete/shipments', [ShipmentController::class, 'complete'])->name('shipments.complete');
    Route::get('autocomplete/shipments', [ShipmentController::class, 'autocomplete'])->name('shipments.autocomplete');

    Route::apiResource('couriers', CourierController::class);
    Route::get('complete/couriers', [CourierController::class, 'complete'])->name('couriers.complete');
    Route::get('autocomplete/couriers', [CourierController::class, 'autocomplete'])->name('couriers.autocomplete');

    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('complete/warehouses', [WarehouseController::class, 'complete'])->name('warehouses.complete');
    Route::get('autocomplete/warehouses', [WarehouseController::class, 'autocomplete'])->name('warehouses.autocomplete');
    Route::get('warehouses/{warehouse}/products', [WarehouseController::class, 'products'])->name('warehouses.products');

    Route::apiResource('warehouse-products', WarehouseProductController::class);
    Route::get('autocomplete/warehouse-products', [WarehouseProductController::class, 'autocomplete'])->name('warehouse-products.autocomplete');
    Route::get('search/warehouse-products', [WarehouseProductController::class, 'search'])->name('warehouse-products.search');
    Route::get('serial-check/warehouse-products', [WarehouseProductController::class, 'serialCheck'])->name('warehouse-products.serial-check');
    Route::put('warehouse-products/{warehouseProduct}/update/barcode-sku', [WarehouseProductController::class, 'updateBarcodeSku'])->name('warehouse-products.update-barcode-sku');

    Route::apiResource('warehouse-stock-adjustments', WarehouseStockAdjustmentController::class);
    Route::get('autocomplete/warehouse-stock-adjustments', [WarehouseStockAdjustmentController::class, 'autocomplete'])->name('warehouse-stock-adjustments.autocomplete');

    Route::apiResource('warehouse-stock-transfers', WarehouseStockTransferController::class);
    Route::get('autocomplete/warehouse-stock-transfers', [WarehouseStockTransferController::class, 'autocomplete'])->name('warehouse-stock-transfers.autocomplete');
    Route::post('warehouse-stock-transfer-details/{detail}/receive', [WarehouseStockTransferDetailController::class, 'receive'])->name('warehouse-stock-transfer-details.receive');
    Route::post('warehouse-stock-transfer-details/{detail}/return', [WarehouseStockTransferDetailController::class, 'return'])->name('warehouse-stock-transfer-details.return');

    Route::apiResource('warehouse-stock-transfer-details', WarehouseStockTransferDetailController::class);
    Route::get('autocomplete/warehouse-stock-transfer-details', [WarehouseStockTransferDetailController::class, 'autocomplete'])->name('warehouse-stock-transfer-details.autocomplete');

    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/export', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
    Route::post('purchase-orders/{purchaseOrder}/pending', [PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/order', [PurchaseOrderController::class, 'order'])->name('purchase-orders.order');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::get('autocomplete/purchase-orders', [PurchaseOrderController::class, 'autocomplete'])->name('purchase-orders.autocomplete');
    Route::get('purchase-orders/{purchaseOrder}/approval-levels', [PurchaseOrderController::class, 'approvalLevels'])->name('purchase-orders.approval-levels');
    Route::get('purchase-orders/{purchaseOrder}/approval-remarks', [PurchaseOrderController::class, 'approvalRemarks'])->name('purchase-orders.approval-remarks');

    // Add nested route for purchase order details
    Route::group(['prefix' => 'purchase-orders/{purchaseOrder}'], function () {
        Route::apiResource('details', PurchaseOrderDetailController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    Route::apiResource('approval-remarks', ApprovalRemarkController::class);

    Route::apiResource('purchase-order-details', PurchaseOrderDetailController::class);
    Route::get('autocomplete/purchase-order-details', [PurchaseOrderDetailController::class, 'autocomplete'])->name('purchase-order-details.autocomplete');

    Route::apiResource('goods-receipt-detail-remarks', GoodsReceiptDetailRemarkController::class);

    Route::apiResource('goods-receipts', GoodsReceiptController::class);
    Route::post('goods-receipts/export', [GoodsReceiptController::class, 'export'])->name('goods-receipts.export');
    Route::get('autocomplete/goods-receipts', [GoodsReceiptController::class, 'autocomplete'])->name('goods-receipts.autocomplete');
    Route::post('goods-receipts/{goodsReceipt}/transfer', [GoodsReceiptController::class, 'transfer'])->name('goods-receipts.transfer');

    Route::apiResource('goods-receipt-details', GoodsReceiptDetailController::class);
    Route::get('autocomplete/goods-receipt-details', [GoodsReceiptDetailController::class, 'autocomplete'])->name('goods-receipt-details.autocomplete');
    Route::post('goods-receipt-details/{goodsReceiptDetail}/receive', [GoodsReceiptDetailController::class, 'receive'])->name('goods-receipt-details.receive');
    Route::post('goods-receipt-details/{goodsReceiptDetail}/return', [GoodsReceiptDetailController::class, 'return'])->name('goods-receipt-details.return');
    Route::post('goods-receipt-details/{goodsReceiptDetail}/sync', [GoodsReceiptDetailController::class, 'sync'])->name('goods-receipt-details.sync');


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

    Route::apiResource('projects-columns', ProjectColumnController::class);
    Route::get('autocomplete/projects-columns', [ProjectColumnController::class, 'autocomplete'])->name('projects-columns.autocomplete');

    Route::apiResource('projects', ProjectController::class);
    Route::get('autocomplete/projects', [ProjectController::class, 'autocomplete'])->name('projects.autocomplete');

    Route::apiResource('project-tasks', ProjectTaskController::class);
    Route::get('autocomplete/project-tasks', [ProjectTaskController::class, 'autocomplete'])->name('project-tasks.autocomplete');

    Route::apiResource('employees', EmployeeController::class);
    Route::put('employees/{employee}/employment-details', [EmployeeController::class, 'updateEmploymentDetails'])->name('employees.update-employment-details');
    Route::put('employees/{employee}/payroll-details', [EmployeeController::class, 'updatePayrollDetails'])->name('employees.update-payroll-details');
    Route::get('autocomplete/employees', [EmployeeController::class, 'autocomplete'])->name('employees.autocomplete');

    // Grouped for employees
    Route::controller(EmployeeLeaveController::class)->group(function () {
        Route::apiResource('employee-leaves', EmployeeLeaveController::class);
        Route::get('autocomplete/employee-leaves', 'autocomplete')->name('employee-leaves.autocomplete');
        Route::post('employee-leaves/{employeeLeave}/approve', [EmployeeLeaveController::class, 'approve'])->name('employee-leaves.approve');
        Route::post('employee-leaves/{employeeLeave}/reject', [EmployeeLeaveController::class, 'reject'])->name('employee-leaves.reject');
    });

    Route::controller(EmployeeOvertimeController::class)->group(function () {
        Route::apiResource('employee-overtimes', EmployeeOvertimeController::class);
        Route::get('autocomplete/employee-overtimes', 'autocomplete')->name('employee-overtimes.autocomplete');
        Route::post('employee-overtimes/{employeeOvertime}/approve', [EmployeeOvertimeController::class, 'approve'])->name('employee-overtimes.approve');
        Route::post('employee-overtimes/{employeeOvertime}/reject', [EmployeeOvertimeController::class, 'reject'])->name('employee-overtimes.reject');
    });

    Route::controller(EmployeeEducationalAttainmentController::class)->group(function () {
        Route::apiResource('employee-educational-attainments', EmployeeEducationalAttainmentController::class);
        Route::get('autocomplete/educational-attainments', 'autocomplete')->name('employee-educational-attainments.autocomplete');
    });

    Route::controller(EmployeeWorkExperienceController::class)->group(function () {
        Route::apiResource('employee-work-experiences', EmployeeWorkExperienceController::class);
        Route::get('autocomplete/work-experiences', 'autocomplete')->name('employee-work-experiences.autocomplete');
    });

    Route::controller(EmployeeContactDetailController::class)->group(function () {
        Route::apiResource('employee-contact-details', EmployeeContactDetailController::class);
        Route::get('autocomplete/contact-details', 'autocomplete')->name('employee-contact-details.autocomplete');
    });

    Route::controller(EmployeeDependentController::class)->group(function () {
        Route::apiResource('employee-dependents', EmployeeDependentController::class);
        Route::get('autocomplete/dependents', 'autocomplete')->name('employee-dependents.autocomplete');
    });

    Route::controller(EmployeeDisciplinaryActionController::class)->group(function () {
        Route::apiResource('employee-disciplinary-actions', EmployeeDisciplinaryActionController::class);
        Route::get('autocomplete/disciplinary-actions', 'autocomplete')->name('employee-disciplinary-actions.autocomplete');
    });

    Route::controller(EmployeeCertificateController::class)->group(function () {
        Route::apiResource('employee-certificates', EmployeeCertificateController::class);
        Route::get('autocomplete/certificates', 'autocomplete')->name('employee-certificates.autocomplete');
    });

    Route::controller(EmployeeDocumentController::class)->group(function () {
        Route::apiResource('employee-documents', EmployeeDocumentController::class);
        Route::get('employee-documents/{id}/download', 'download')->name('employee-documents.download');
        Route::get('autocomplete/documents', 'autocomplete')->name('employee-documents.autocomplete');
    });

    Route::controller(EmployeeAwardController::class)->group(function () {
        Route::apiResource('employee-awards', EmployeeAwardController::class);
        Route::get('autocomplete/awards', 'autocomplete')->name('employee-awards.autocomplete');
    });

    Route::controller(EmployeeSkillController::class)->group(function () {
        Route::apiResource('employee-skills', EmployeeSkillController::class);
        Route::get('autocomplete/skills', 'autocomplete')->name('employee-skills.autocomplete');
    });

    Route::controller(EmployeePerformanceReviewController::class)->group(function () {
        Route::apiResource('employee-performance-reviews', EmployeePerformanceReviewController::class);
        Route::get('autocomplete/performance-reviews', 'autocomplete')->name('employee-performance-reviews.autocomplete');
    });

    Route::controller(EmployeeAttendanceDetailController::class)->group(function () {
        Route::apiResource('employee-attendance-details', EmployeeAttendanceDetailController::class);
        Route::get('autocomplete/attendance-details', 'autocomplete')->name('employee-attendance-details.autocomplete');
    });

    Route::apiResource('holidays', HolidayController::class);
    Route::get('autocomplete/holidays', [HolidayController::class, 'autocomplete'])->name('holidays.autocomplete');
    Route::get('complete/holidays', [HolidayController::class, 'complete'])->name('holidays.complete');

    Route::apiResource('departments', DepartmentController::class);
    Route::get('autocomplete/departments', [DepartmentController::class, 'autocomplete'])->name('departments.autocomplete');
    Route::get('complete/departments', [DepartmentController::class, 'complete'])->name('departments.complete');

    Route::apiResource('deductions', DeductionController::class);
    Route::get('autocomplete/deductions', [DeductionController::class, 'autocomplete'])->name('deductions.autocomplete');
    Route::get('complete/deductions', [DeductionController::class, 'complete'])->name('deductions.complete');

    Route::apiResource('document-types', DocumentTypeController::class);
    Route::get('autocomplete/document-types', [DocumentTypeController::class, 'autocomplete'])->name('document-types.autocomplete');
    Route::get('complete/document-types', [DocumentTypeController::class, 'complete'])->name('document-types.complete');

    Route::apiResource('offense-types', OffenseTypeController::class);
    Route::get('autocomplete/offense-types', [OffenseTypeController::class, 'autocomplete'])->name('offense-types.autocomplete');
    Route::get('complete/offense-types', [OffenseTypeController::class, 'complete'])->name('offense-types.complete');

    Route::apiResource('positions', PositionController::class);
    Route::get('autocomplete/positions', [PositionController::class, 'autocomplete'])->name('positions.autocomplete');

    Route::post('app-settings/schedule', [SettingController::class, 'updateSchedule']);
    Route::get('app-settings/export-database', [SettingController::class, 'exportDatabase']);
    Route::post('app-settings/import-database', [SettingController::class, 'importDatabase']);
    Route::put('app-settings/environment/update', [SettingController::class, 'environment'])->name('app.settings.environment.update');
    Route::put('app-settings/style/update', [SettingController::class, 'style'])->name('app.settings.style.update');
    Route::apiResource('app-settings', SettingController::class)->only(['show', 'update']);

    Route::apiResource('activity-logs', ActivityLogController::class);

    Route::get('/notifications', function () {
        $user = Auth::user();
        return response()->json([
            'notifications' => DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->get(),
            'unread_count' => DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->count(),
        ]);
    });

    Route::post('/notifications/{id}/read', function ($id) {
        $notification = DatabaseNotification::find($id);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    });

    Route::post('/notifications/mark-all-read', function () {
        DatabaseNotification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    });

    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::get('autocomplete/purchase-requisitions', [PurchaseRequisitionController::class, 'autocomplete'])->name('purchase-requisitions.autocomplete');

    Route::apiResource('product-variation-attributes', ProductVariationAttributeController::class);
    Route::post('product-variation-attributes/{id}/restore', [ProductVariationAttributeController::class, 'restore'])->name('product-variation-attributes.restore');

    Route::apiResource('accounts', AccountController::class);
    Route::get('autocomplete/accounts', [AccountController::class, 'autocomplete'])->name('accounts.autocomplete');
    Route::post('accounts/{account}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

    Route::apiResource('account-types', AccountTypeController::class);
    Route::get('autocomplete/account-types', [AccountTypeController::class, 'autocomplete'])->name('account-types.autocomplete');
    Route::post('account-types/{accountType}/restore', [AccountTypeController::class, 'restore'])->name('account-types.restore');

    Route::apiResource('warehouse-stock-transfer-serials', App\Http\Controllers\Api\Modules\WarehouseManagement\WarehouseStockTransferSerialController::class);
});
