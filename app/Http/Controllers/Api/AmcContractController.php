<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AmcContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmcContractController extends Controller
{
    /**
     * List AMC contracts.
     *
     * @OA\Get(
     *     path="/solar/amc-contracts",
     *     tags={"Solar - Service & AMC"},
     *     summary="List AMC Contracts",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","expired","renewed"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = AmcContract::query()->with(['installation', 'customer']);

        if ($user->role !== 'super_admin') {
            $query->where('organization_id', $user->organization_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $contracts = $query->latest()->paginate($perPage);

        return response()->json($contracts);
    }

    /**
     * Create AMC contract (after installation).
     *
     * @OA\Post(
     *     path="/solar/amc-contracts",
     *     tags={"Solar - Service & AMC"},
     *     summary="Create AMC Contract",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"installation_id","start_date","end_date","amount"},
     *         @OA\Property(property="installation_id", type="integer"),
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="start_date", type="string", format="date"),
     *         @OA\Property(property="end_date", type="string", format="date"),
     *         @OA\Property(property="amount", type="number")
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
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['status'] = 'active';

        $contract = AmcContract::create($validated);
        $contract->load(['installation', 'customer']);

        return response()->json($contract, 201);
    }

    /**
     * Get AMC contract.
     *
     * @OA\Get(
     *     path="/solar/amc-contracts/{id}",
     *     tags={"Solar - Service & AMC"},
     *     summary="Get AMC Contract",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $contract = AmcContract::with(['installation', 'customer'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $contract->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($contract);
    }

    /**
     * Update AMC contract.
     *
     * @OA\Put(
     *     path="/solar/amc-contracts/{id}",
     *     tags={"Solar - Service & AMC"},
     *     summary="Update AMC Contract",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="end_date", type="string", format="date"),
     *         @OA\Property(property="amount", type="number"),
     *         @OA\Property(property="status", type="string", enum={"active","expired","renewed"})
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contract = AmcContract::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $contract->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'end_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,expired,renewed'],
        ]);
        $contract->update($validated);
        $contract->load(['installation', 'customer']);

        return response()->json($contract);
    }
}
