<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTicketController extends Controller
{
    /**
     * List service tickets.
     *
     * @OA\Get(
     *     path="/solar/service-tickets",
     *     tags={"Solar - Service & AMC"},
     *     summary="List Service Tickets",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"open","assigned","in_progress","resolved","closed"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = ServiceTicket::query()->with(['installation', 'customer', 'assignedTo']);

        if ($user->role !== 'super_admin') {
            $query->where('organization_id', $user->organization_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $tickets = $query->latest()->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Create service ticket (Customer complaint / AMC).
     *
     * @OA\Post(
     *     path="/solar/service-tickets",
     *     tags={"Solar - Service & AMC"},
     *     summary="Create Service Ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"complaint"},
     *         @OA\Property(property="installation_id", type="integer"),
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="complaint", type="string"),
     *         @OA\Property(property="priority", type="string", enum={"low","medium","high"})
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $orgId = auth('api')->user()?->organization_id;
        if (! $orgId) {
            return response()->json(['message' => 'No branch context.'], 403);
        }

        $validated = $request->validate([
            'installation_id' => ['nullable', 'integer', 'exists:installations,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'complaint' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['status'] = 'open';
        $validated['ticket_number'] = 'SRV-' . str_pad((string) (ServiceTicket::where('organization_id', $orgId)->count() + 1), 5, '0', STR_PAD_LEFT);

        $ticket = ServiceTicket::create($validated);
        $ticket->load(['installation', 'customer']);

        return response()->json($ticket, 201);
    }

    /**
     * Get service ticket.
     *
     * @OA\Get(
     *     path="/solar/service-tickets/{id}",
     *     tags={"Solar - Service & AMC"},
     *     summary="Get Service Ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $ticket = ServiceTicket::with(['installation', 'customer', 'assignedTo'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $ticket->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($ticket);
    }

    /**
     * Update ticket (assign, resolve).
     *
     * @OA\Put(
     *     path="/solar/service-tickets/{id}",
     *     tags={"Solar - Service & AMC"},
     *     summary="Update Service Ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="assigned_to", type="integer"),
     *         @OA\Property(property="status", type="string", enum={"open","assigned","in_progress","resolved","closed"}),
     *         @OA\Property(property="resolution", type="string"),
     *         @OA\Property(property="scheduled_date", type="string", format="date")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $ticket = ServiceTicket::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $ticket->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:open,assigned,in_progress,resolved,closed'],
            'resolution' => ['nullable', 'string'],
            'scheduled_date' => ['nullable', 'date'],
        ]);
        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'closed'])) {
            $validated['resolved_at'] = now();
        }
        $ticket->update($validated);
        $ticket->load(['assignedTo']);

        return response()->json($ticket);
    }
}
