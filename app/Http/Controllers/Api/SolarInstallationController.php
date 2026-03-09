<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\InstallationAssignment;
use App\Models\InstallationChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolarInstallationController extends Controller
{
    /**
     * List installations (Installation Manager / Branch Manager).
     *
     * @OA\Get(
     *     path="/solar/installations",
     *     tags={"Solar - Installations"},
     *     summary="List Installations",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"scheduled","material_dispatched","in_progress","quality_check","completed","cancelled"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Installation::query()->with(['lead', 'quotation', 'customer', 'installationManager', 'assignments.user']);

        if ($user->role !== 'super_admin') {
            $query->where('organization_id', $user->organization_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $installations = $query->latest()->paginate($perPage);

        return response()->json($installations);
    }

    /**
     * Create installation (after quotation approved).
     *
     * @OA\Post(
     *     path="/solar/installations",
     *     tags={"Solar - Installations"},
     *     summary="Create Installation",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"organization_id"},
     *         @OA\Property(property="lead_id", type="integer"),
     *         @OA\Property(property="quotation_id", type="integer"),
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="installation_manager_id", type="integer"),
     *         @OA\Property(property="scheduled_date", type="string", format="date"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $orgId = $user->role === 'super_admin' ? $request->input('organization_id') : $user->organization_id;
        if (! $orgId) {
            return response()->json(['message' => 'No branch context.'], 403);
        }

        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'quotation_id' => ['nullable', 'integer', 'exists:quotations,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'installation_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['installation_manager_id'] = $validated['installation_manager_id'] ?? $user->id;
        $validated['status'] = 'scheduled';
        $validated['installation_number'] = 'INST-' . str_pad((string) (Installation::where('organization_id', $orgId)->count() + 1), 5, '0', STR_PAD_LEFT);

        $installation = Installation::create($validated);

        // Default checklist
        $tasks = ['Panel mounting', 'Wiring', 'Inverter installation', 'Grid connection', 'System testing'];
        foreach ($tasks as $i => $task) {
            InstallationChecklist::create([
                'installation_id' => $installation->id,
                'task_name' => $task,
                'sort_order' => $i + 1,
            ]);
        }

        $installation->load(['lead', 'quotation', 'customer', 'installationManager', 'checklists']);

        return response()->json($installation, 201);
    }

    /**
     * Get installation with checklist and technicians.
     *
     * @OA\Get(
     *     path="/solar/installations/{id}",
     *     tags={"Solar - Installations"},
     *     summary="Get Installation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $installation = Installation::with(['lead', 'quotation', 'customer', 'installationManager', 'assignments.user', 'checklists', 'photos'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $installation->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($installation);
    }

    /**
     * Update installation (schedule, status).
     *
     * @OA\Put(
     *     path="/solar/installations/{id}",
     *     tags={"Solar - Installations"},
     *     summary="Update Installation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="scheduled_date", type="string", format="date"),
     *         @OA\Property(property="status", type="string", enum={"scheduled","material_dispatched","in_progress","quality_check","completed","cancelled"}),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $installation = Installation::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $installation->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'scheduled_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:scheduled,material_dispatched,in_progress,quality_check,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);
        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        $installation->update($validated);
        $installation->load(['checklists', 'assignments.user']);

        return response()->json($installation);
    }

    /**
     * Assign technicians to installation.
     *
     * @OA\Post(
     *     path="/solar/installations/{id}/assign",
     *     tags={"Solar - Installations"},
     *     summary="Assign Technicians",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="user_ids", type="array", @OA\Items(type="integer"), description="Technician user IDs")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $installation = Installation::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $installation->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        InstallationAssignment::where('installation_id', $id)->delete();
        foreach ($validated['user_ids'] as $uid) {
            InstallationAssignment::create([
                'installation_id' => $id,
                'user_id' => $uid,
                'role' => 'technician',
            ]);
        }

        $installation->load(['assignments.user']);
        return response()->json($installation);
    }

    /**
     * Mark checklist task complete (Technician).
     *
     * @OA\Patch(
     *     path="/solar/installations/{id}/checklist/{checklist_id}",
     *     tags={"Solar - Installations"},
     *     summary="Update Checklist Task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="checklist_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="completed", type="boolean"))),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function updateChecklist(Request $request, int $id, int $checklist_id): JsonResponse
    {
        $installation = Installation::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $installation->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $item = InstallationChecklist::where('installation_id', $id)->findOrFail($checklist_id);
        $item->update([
            'completed' => $request->boolean('completed', true),
            'completed_at' => $request->boolean('completed', true) ? now() : null,
        ]);
        return response()->json($item);
    }
}
