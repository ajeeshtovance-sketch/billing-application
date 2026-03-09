<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AmcContract;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\ServiceTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolarDashboardController extends Controller
{
    /**
     * Solar dashboard metrics. MD Super Admin: all branches; Branch Manager: own branch.
     *
     * @OA\Get(
     *     path="/solar/dashboard",
     *     tags={"Solar - Dashboard"},
     *     summary="Solar Dashboard Metrics",
     *     description="Total leads, installations, revenue branch-wise, pending installations, technician productivity, AMC renewals, sales conversion. AI-friendly summary.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="organization_id", in="query", description="Branch (MD Super Admin only)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="period", in="query", description="T=Today, W=Week, M=Month, Y=Year", @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="total_leads", type="integer"),
     *         @OA\Property(property="leads_today", type="integer"),
     *         @OA\Property(property="total_installations", type="integer"),
     *         @OA\Property(property="pending_installations", type="integer"),
     *         @OA\Property(property="completed_installations", type="integer"),
     *         @OA\Property(property="installations_scheduled", type="integer", description="Scheduled/material_dispatched/in_progress"),
     *         @OA\Property(property="quotations_sent_today", type="integer"),
     *         @OA\Property(property="branch_revenue", type="number", description="Branch paid revenue (when org scoped)"),
     *         @OA\Property(property="revenue_branch_wise", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="service_tickets_pending", type="integer"),
     *         @OA\Property(property="amc_renewals_due", type="integer"),
     *         @OA\Property(property="conversion_rate", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $orgId = $user->role === 'super_admin' ? $request->input('organization_id') : $user->organization_id;
        $period = $request->input('period', 'M');

        $leadQuery = Lead::query();
        $instQuery = Installation::query();
        $ticketQuery = ServiceTicket::query()->whereIn('status', ['open', 'assigned', 'in_progress']);
        $amcQuery = AmcContract::query()->where('status', 'active');

        if ($orgId) {
            $leadQuery->where('organization_id', $orgId);
            $instQuery->where('organization_id', $orgId);
            $ticketQuery->where('organization_id', $orgId);
            $amcQuery->where('organization_id', $orgId);
        }

        $totalLeads = $leadQuery->count();
        $leadsToday = (clone $leadQuery)->whereDate('created_at', today())->count();
        $totalInstallations = (clone $instQuery)->count();
        $pendingInstallations = (clone $instQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();
        $completedInstallations = (clone $instQuery)->where('status', 'completed')->count();
        $serviceTicketsPending = $ticketQuery->count();
        $amcRenewalsDue = (clone $amcQuery)->where('end_date', '<=', now()->addDays(30))->count();

        $confirmedLeads = Lead::when($orgId, fn ($q) => $q->where('organization_id', $orgId))->whereIn('status', ['confirmed', 'installation'])->count();
        $conversionRate = $totalLeads > 0 ? round($confirmedLeads / $totalLeads * 100, 2) : 0;

        // Branch Manager / Branch Admin metrics
        $quotationQuery = Quotation::query()->when($orgId, fn ($q) => $q->where('organization_id', $orgId));
        $quotationsSentToday = (clone $quotationQuery)->whereDate('created_at', today())->count();
        $installationsScheduled = (clone $instQuery)->whereIn('status', ['scheduled', 'material_dispatched', 'in_progress'])->count();
        $branchRevenue = 0;
        if ($orgId) {
            $branchRevenue = round(Invoice::where('organization_id', $orgId)->where('status', 'paid')->sum('amount_paid'), 2);
        }

        $revenueBranchWise = [];
        if ($user->role === 'super_admin' && ! $orgId) {
            $revenueBranchWise = \App\Models\Organization::query()
                ->select('id', 'name')
                ->get()
                ->map(function ($org) use ($period) {
                    $q = Invoice::where('organization_id', $org->id)->where('status', 'paid');
                    $this->applyPeriod($q, $period, 'issue_date');
                    return [
                        'organization_id' => $org->id,
                        'branch_name' => $org->name,
                        'revenue' => round($q->sum('amount_paid'), 2),
                    ];
                });
        }

        return response()->json([
            'total_leads' => $totalLeads,
            'leads_today' => $leadsToday,
            'total_installations' => $totalInstallations,
            'pending_installations' => $pendingInstallations,
            'completed_installations' => $completedInstallations,
            'installations_scheduled' => $installationsScheduled,
            'quotations_sent_today' => $quotationsSentToday,
            'branch_revenue' => $branchRevenue,
            'revenue_branch_wise' => $revenueBranchWise,
            'service_tickets_pending' => $serviceTicketsPending,
            'amc_renewals_due' => $amcRenewalsDue,
            'conversion_rate' => $conversionRate,
            'period' => $period,
        ]);
    }

    private function applyPeriod($query, string $period, string $dateColumn): void
    {
        switch ($period) {
            case 'T':
                $query->whereDate($dateColumn, today());
                break;
            case 'W':
                $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'M':
                $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year);
                break;
            case 'Y':
                $query->whereYear($dateColumn, now()->year);
                break;
        }
    }
}
