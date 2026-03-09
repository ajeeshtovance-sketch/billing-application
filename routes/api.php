<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\CreditNoteController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryChallanController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\AmcContractController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceTicketController;
use App\Http\Controllers\Api\SolarDashboardController;
use App\Http\Controllers\Api\SolarInstallationController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - JWT Secured
|--------------------------------------------------------------------------
| All routes use JWT authentication. Include header:
| Authorization: Bearer {token}
|
*/

Route::prefix('v1')->group(function () {
    // Public routes (must NEVER require JWT)
    Route::post('auth/login', [AuthController::class, 'login'])->withoutMiddleware('auth:api');
    Route::post('auth/register', [AuthController::class, 'register'])->withoutMiddleware('auth:api');
    Route::post('auth/validate-token', [AuthController::class, 'validateToken'])->withoutMiddleware('auth:api'); // Debug endpoint

    // Protected routes - require JWT
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('auth/permissions', [AuthController::class, 'permissions']);

        // Dashboard (org users)
        Route::get('dashboard/summary', [AuthController::class, 'dashboardSummary']);
        Route::get('dashboard/net-profit', [DashboardController::class, 'netProfit']);
        Route::get('dashboard/received-amount', [DashboardController::class, 'receivedAmount']);
        Route::get('dashboard/income-expense', [DashboardController::class, 'incomeExpense']);
        Route::get('dashboard/low-stock-items', [DashboardController::class, 'lowStockItems']);
        Route::get('dashboard/payment-method-chart', [DashboardController::class, 'paymentMethodChart']);
        Route::get('dashboard/cta', [DashboardController::class, 'cta']);

        // CTA - New Bill, Add Product, Add Customer
        Route::post('bills', [BillController::class, 'store']);

        // Sales - Invoices
        Route::get('invoices/summary', [InvoiceController::class, 'summary']);
        Route::patch('invoices/{id}/cancel', [InvoiceController::class, 'cancel']);
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'update', 'destroy'])->parameters(['invoice' => 'id']);

        // Sales - Quotations
        Route::post('quotations/{id}/convert-to-invoice', [QuotationController::class, 'convertToInvoice']);
        Route::resource('quotations', QuotationController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['quotation' => 'id']);

        // Sales - Delivery Challans
        Route::patch('delivery-challans/{id}/mark-delivered', [DeliveryChallanController::class, 'markDelivered']);
        Route::post('delivery-challans/{id}/convert-to-invoice', [DeliveryChallanController::class, 'convertToInvoice']);
        Route::resource('delivery-challans', DeliveryChallanController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['delivery_challan' => 'id']);

        // Sales - Credit Notes
        Route::patch('credit-notes/{id}/mark-refund', [CreditNoteController::class, 'markRefund']);
        Route::resource('credit-notes', CreditNoteController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['credit_note' => 'id']);

        // Products (list, summary, CRUD, update stock)
        Route::get('products/summary', [ProductController::class, 'summary']);
        Route::patch('products/{id}/stock', [ProductController::class, 'updateStock']);
        Route::resource('products', ProductController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['product' => 'id']);

        // Customers (list, summary, CRUD, invoices)
        Route::get('customers/summary', [CustomerController::class, 'summary']);
        Route::get('customers/{id}/invoices', [CustomerController::class, 'invoices']);
        Route::resource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['customer' => 'id']);

        // Super Admin - SaaS management
        Route::middleware('super_admin')->prefix('super-admin')->group(function () {
            Route::get('dashboard', [SuperAdminController::class, 'dashboard']);
            Route::post('login-as/{user}', [SuperAdminController::class, 'loginAs']);
            Route::apiResource('organizations', OrganizationController::class);
            Route::apiResource('roles', RoleController::class);
            Route::get('permissions', [PermissionController::class, 'index']);
        });

        // Admin - Sub-user management (super_admin + org admin)
        Route::middleware('org_admin')->prefix('admin')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        // Solar ERP - Lead → Survey → Quotation → Installation → Service & AMC
        Route::prefix('solar')->group(function () {
            Route::get('dashboard', [SolarDashboardController::class, 'index']);
            Route::apiResource('leads', LeadController::class)->parameters(['lead' => 'id']);
            Route::apiResource('surveys', SurveyController::class)->only(['index', 'store', 'show', 'update'])->parameters(['survey' => 'id']);
            Route::get('installations', [SolarInstallationController::class, 'index']);
            Route::post('installations', [SolarInstallationController::class, 'store']);
            Route::get('installations/{id}', [SolarInstallationController::class, 'show']);
            Route::put('installations/{id}', [SolarInstallationController::class, 'update']);
            Route::post('installations/{id}/assign', [SolarInstallationController::class, 'assign']);
            Route::patch('installations/{id}/checklist/{checklist_id}', [SolarInstallationController::class, 'updateChecklist']);
            Route::apiResource('service-tickets', ServiceTicketController::class)->only(['index', 'store', 'show', 'update'])->parameters(['service_ticket' => 'id']);
            Route::apiResource('amc-contracts', AmcContractController::class)->only(['index', 'store', 'show', 'update'])->parameters(['amc_contract' => 'id']);
            Route::apiResource('vendors', VendorController::class)->parameters(['vendor' => 'id']);
        });
    });
});
