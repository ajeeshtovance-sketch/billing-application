<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * List vendors (branch or all for MD Super Admin).
     *
     * @OA\Get(
     *     path="/solar/vendors",
     *     tags={"Solar - Vendors"},
     *     summary="List Vendors",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Vendor::query();

        if ($user->role !== 'super_admin') {
            $query->where('organization_id', $user->organization_id);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")->orWhere('contact_person', 'like', "%{$q}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $vendors = $query->latest()->paginate($perPage);

        return response()->json($vendors);
    }

    /**
     * Create vendor (MD Super Admin / Branch).
     *
     * @OA\Post(
     *     path="/solar/vendors",
     *     tags={"Solar - Vendors"},
     *     summary="Create Vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="contact_person", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="address", type="object"),
     *         @OA\Property(property="gstin", type="string"),
     *         @OA\Property(property="notes", type="string")
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
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['organization_id'] = $orgId;

        $vendor = Vendor::create($validated);
        return response()->json($vendor, 201);
    }

    /**
     * Get vendor.
     *
     * @OA\Get(
     *     path="/solar/vendors/{id}",
     *     tags={"Solar - Vendors"},
     *     summary="Get Vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $vendor->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($vendor);
    }

    /**
     * Update vendor.
     *
     * @OA\Put(
     *     path="/solar/vendors/{id}",
     *     tags={"Solar - Vendors"},
     *     summary="Update Vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="contact_person", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="address", type="object"),
     *         @OA\Property(property="gstin", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $vendor->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);
        $vendor->update($validated);
        return response()->json($vendor);
    }

    /**
     * Delete vendor.
     *
     * @OA\Delete(
     *     path="/solar/vendors/{id}",
     *     tags={"Solar - Vendors"},
     *     summary="Delete Vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="No Content"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $vendor->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $vendor->delete();
        return response()->json(null, 204);
    }
}
