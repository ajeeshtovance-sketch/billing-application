<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    /**
     * Super Admin Dashboard - SaaS-wide stats.
     *
     * @OA\Get(
     *     path="/super-admin/dashboard",
     *     tags={"Super Admin"},
     *     summary="Super Admin Dashboard",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"today","week","month","year"}, default="month")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="total_organizations", type="integer"),
     *         @OA\Property(property="active_organizations", type="integer"),
     *         @OA\Property(property="total_users", type="integer"),
     *         @OA\Property(property="total_sales", type="number"),
     *         @OA\Property(property="paid", type="number"),
     *         @OA\Property(property="unpaid", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        $organizations = Organization::query();
        $totalOrgs = $organizations->count();
        $activeOrgs = (clone $organizations)->where('status', 'active')->count();

        $totalUsers = User::whereNotNull('organization_id')->count();

        $invoiceQuery = Invoice::query();
        switch ($period) {
            case 'today':
                $invoiceQuery->whereDate('issue_date', today());
                break;
            case 'week':
                $invoiceQuery->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $invoiceQuery->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year);
                break;
            case 'year':
                $invoiceQuery->whereYear('issue_date', now()->year);
                break;
        }

        $invoices = $invoiceQuery->get();
        $totalSales = $invoices->whereNotIn('status', ['cancelled'])->sum('total');
        $paid = $invoices->where('status', 'paid')->sum('amount_paid');
        $unpaid = $invoices->whereIn('status', ['sent', 'unpaid', 'partial'])->sum('balance_due');

        return response()->json([
            'total_organizations' => $totalOrgs,
            'active_organizations' => $activeOrgs,
            'total_users' => $totalUsers,
            'total_sales' => round($totalSales, 2),
            'paid' => round($paid, 2),
            'unpaid' => round($unpaid, 2),
            'period' => $period,
        ]);
    }

    /**
     * Login as sub-admin (impersonate). Super admin gets a JWT for the target user.
     *
     * @OA\Post(
     *     path="/super-admin/login-as/{user_id}",
     *     tags={"Super Admin"},
     *     summary="Login as Sub-Admin",
     *     description="Super admin obtains a JWT token for the target user to access their organization view",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string"),
     *         @OA\Property(property="token_type", type="string", example="bearer"),
     *         @OA\Property(property="expires_in", type="integer"),
     *         @OA\Property(property="impersonating", type="object", description="Target user info")
     *     )),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function loginAs(User $user): JsonResponse
    {
        if ($user->role === 'super_admin') {
            return response()->json(['message' => 'Cannot impersonate another super admin.'], 403);
        }

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'impersonating' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'organization_id' => $user->organization_id,
            ],
        ]);
    }
}
