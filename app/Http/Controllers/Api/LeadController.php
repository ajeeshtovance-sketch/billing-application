<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    /**
     * List leads. Branch Manager / Admin / Sales see their branch; MD Super Admin can filter by branch.
     *
     * @OA\Get(
     *     path="/solar/leads",
     *     tags={"Solar - Leads"},
     *     summary="List Leads",
     *     description="CRM lead list. Pipeline: new → contacted → site_survey → proposal → negotiation → confirmed → installation → closed_lost",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", description="Customer name, phone", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"new","contacted","site_survey","proposal","negotiation","confirmed","installation","closed_lost"})),
     *     @OA\Parameter(name="lead_source", in="query", @OA\Schema(type="string", enum={"website","facebook_ads","google_ads","whatsapp","referral","walk_in","phone"})),
     *     @OA\Parameter(name="assigned_to", in="query", description="Sales executive user ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="organization_id", in="query", description="Branch (MD Super Admin only)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Lead::query()->with(['assignedTo:id,name,email', 'organization:id,name']);

        $orgId = $user->role === 'super_admin' ? $request->input('organization_id') : $user->organization_id;
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $leads = $query->latest()->paginate($perPage);

        return response()->json($leads);
    }

    /**
     * Create lead. Branch Admin / Receptionist / Sales.
     *
     * @OA\Post(
     *     path="/solar/leads",
     *     tags={"Solar - Leads"},
     *     summary="Create Lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_name","phone"},
     *         @OA\Property(property="customer_name", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="address", type="object"),
     *         @OA\Property(property="electricity_bill_amount", type="number"),
     *         @OA\Property(property="location_gps", type="string"),
     *         @OA\Property(property="roof_type", type="string"),
     *         @OA\Property(property="lead_source", type="string", enum={"website","facebook_ads","google_ads","whatsapp","referral","walk_in","phone"}),
     *         @OA\Property(property="assigned_to", type="integer", description="Sales executive user ID"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return response()->json(['message' => 'No branch context.'], 403);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'array'],
            'electricity_bill_amount' => ['nullable', 'numeric', 'min:0'],
            'location_gps' => ['nullable', 'string', 'max:255'],
            'roof_type' => ['nullable', 'string', 'max:50'],
            'lead_source' => ['nullable', 'string', 'in:website,facebook_ads,google_ads,whatsapp,referral,walk_in,phone'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['created_by'] = auth('api')->id();
        $validated['status'] = 'new';
        $validated['lead_number'] = 'L-' . str_pad((string) (Lead::where('organization_id', $orgId)->count() + 1), 5, '0', STR_PAD_LEFT);

        $lead = Lead::create($validated);
        $lead->load(['assignedTo:id,name', 'organization:id,name']);

        return response()->json($lead, 201);
    }

    /**
     * Get single lead.
     *
     * @OA\Get(
     *     path="/solar/leads/{id}",
     *     tags={"Solar - Leads"},
     *     summary="Get Lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $lead = Lead::with(['assignedTo', 'survey', 'quotations', 'organization'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $lead->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($lead);
    }

    /**
     * Update lead (status, assign, follow-up).
     *
     * @OA\Put(
     *     path="/solar/leads/{id}",
     *     tags={"Solar - Leads"},
     *     summary="Update Lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="customer_name", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="address", type="object"),
     *         @OA\Property(property="electricity_bill_amount", type="number"),
     *         @OA\Property(property="roof_type", type="string"),
     *         @OA\Property(property="lead_source", type="string"),
     *         @OA\Property(property="assigned_to", type="integer"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="follow_up_date", type="string", format="date"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $lead->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'array'],
            'electricity_bill_amount' => ['nullable', 'numeric', 'min:0'],
            'location_gps' => ['nullable', 'string', 'max:255'],
            'roof_type' => ['nullable', 'string', 'max:50'],
            'lead_source' => ['nullable', 'string', 'in:website,facebook_ads,google_ads,whatsapp,referral,walk_in,phone'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:new,contacted,site_survey,proposal,negotiation,confirmed,installation,closed_lost'],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update($validated);
        $lead->load(['assignedTo:id,name', 'organization:id,name']);

        return response()->json($lead);
    }

    /**
     * Delete lead.
     *
     * @OA\Delete(
     *     path="/solar/leads/{id}",
     *     tags={"Solar - Leads"},
     *     summary="Delete Lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="No Content"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $lead->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $lead->delete();
        return response()->json(null, 204);
    }
}
