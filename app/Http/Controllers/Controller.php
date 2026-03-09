<?php
namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Billing & Solar ERP API",
 *     version="2.0",
 *     description="Billing + Cloud Solar Panel Installation & Service Management API. Workflow: Lead → Survey → Quotation → Installation → Service & AMC. Roles: MD Super Admin, Branch Manager, Branch Admin, Sales Executive, Site Survey Engineer, Installation Manager/Technician, Service Team, Accounts. JWT Secured: use POST /api/v1/auth/login then header Authorization: Bearer {access_token}. AI-friendly: structured JSON responses, clear field names, dashboard metrics."
 * )
 *
 * Tag order for API documentation (display order in Swagger UI)
 * @OA\Tag(name="Authentication", description="Login, register, logout, refresh, profile, permissions")
 * @OA\Tag(name="Dashboard", description="Org dashboard: summary, net profit, received amount, income-expense, low stock, payment chart, CTA")
 * @OA\Tag(name="CTA", description="Quick actions: New Bill")
 * @OA\Tag(name="Customers", description="Customer CRUD, summary, invoices")
 * @OA\Tag(name="Products", description="Products/items CRUD, summary, stock")
 * @OA\Tag(name="Sales - Invoices", description="Invoice list, summary, create/update/cancel")
 * @OA\Tag(name="Sales - Quotations", description="Quotations, convert to invoice")
 * @OA\Tag(name="Sales - Delivery Challans", description="Delivery challans, mark delivered, convert to invoice")
 * @OA\Tag(name="Sales - Credit Notes", description="Credit notes, mark refund")
 * @OA\Tag(name="Super Admin", description="MD Super Admin: dashboard, login-as")
 * @OA\Tag(name="Super Admin - Organizations", description="Branches CRUD (Super Admin only)")
 * @OA\Tag(name="Super Admin - Roles", description="Roles CRUD, assign permissions (Super Admin only)")
 * @OA\Tag(name="Super Admin - Permissions", description="List permissions (Super Admin only)")
 * @OA\Tag(name="Admin - Users", description="Sub-users / org users CRUD")
 * @OA\Tag(name="Solar - Dashboard", description="Solar ERP metrics: leads, installations, revenue, AMC")
 * @OA\Tag(name="Solar - Leads", description="CRM leads, pipeline, assign")
 * @OA\Tag(name="Solar - Site Survey", description="Site surveys, report, system size")
 * @OA\Tag(name="Solar - Installations", description="Installations, assign technicians, checklist")
 * @OA\Tag(name="Solar - Service & AMC", description="Service tickets, AMC contracts")
 * @OA\Tag(name="Solar - Vendors", description="Vendor management")
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="API Server (localhost)"
 * )
 *
 * @OA\Server(
 *     url="https://testbillapi.eazycutz.com/api/v1",
 *     description="API Server (Live)"
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server (relative)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token"
 * )
 *
 * @OA\SecurityRequirement(
 *     name="bearerAuth",
 *     scopes={}
 * )
 */
abstract class Controller
{
    //
}