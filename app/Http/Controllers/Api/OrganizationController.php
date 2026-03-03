<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * List all organizations (Super Admin only).
     *
     * @OA\Get(
     *     path="/super-admin/organizations",
     *     tags={"Super Admin - Organizations"},
     *     summary="List Organizations",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","suspended","trial"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()->withCount('users');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $organizations = $query->latest()->paginate($perPage);

        return response()->json($organizations);
    }

    /**
     * Create organization (Super Admin only).
     *
     * @OA\Post(
     *     path="/super-admin/organizations",
     *     tags={"Super Admin - Organizations"},
     *     summary="Create Organization",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="legal_name", type="string"),
     *         @OA\Property(property="base_currency", type="string", example="INR"),
     *         @OA\Property(property="status", type="string", enum={"active","suspended","trial"})
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,suspended,trial'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['base_currency'] = $validated['base_currency'] ?? 'INR';

        $organization = Organization::create($validated);

        return response()->json($organization, 201);
    }

    /**
     * Show organization (Super Admin only).
     *
     * @OA\Get(
     *     path="/super-admin/organizations/{id}",
     *     tags={"Super Admin - Organizations"},
     *     summary="Get Organization",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(Organization $organization): JsonResponse
    {
        $organization->loadCount('users');

        return response()->json($organization);
    }

    /**
     * Update organization (Super Admin only).
     *
     * @OA\Put(
     *     path="/super-admin/organizations/{id}",
     *     tags={"Super Admin - Organizations"},
     *     summary="Update Organization",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="legal_name", type="string"),
     *         @OA\Property(property="base_currency", type="string"),
     *         @OA\Property(property="status", type="string", enum={"active","suspended","trial"})
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,suspended,trial'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        if (isset($validated['user_limit']) && $organization->users()->count() > $validated['user_limit']) {
            return response()->json(['message' => 'Cannot set user limit below current user count.'], 422);
        }

        $organization->update($validated);

        return response()->json($organization);
    }

    /**
     * Delete organization (Super Admin only).
     *
     * @OA\Delete(
     *     path="/super-admin/organizations/{id}",
     *     tags={"Super Admin - Organizations"},
     *     summary="Delete Organization",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="No Content"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json(null, 204);
    }
}
