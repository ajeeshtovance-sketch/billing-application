<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * List all roles (Super Admin only). Optionally exclude super_admin role.
     *
     * @OA\Get(
     *     path="/super-admin/roles",
     *     tags={"Super Admin - Roles"},
     *     summary="List Roles",
     *     description="List all roles with user count and permission count. Super Admin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="exclude_super_admin", in="query", description="Exclude super_admin role from list", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="array", @OA\Items(type="object",
     *         @OA\Property(property="id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="users_count", type="integer"),
     *         @OA\Property(property="permissions_count", type="integer")
     *     ))),
     *     @OA\Response(response=403, description="Forbidden - Super Admin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::withCount(['users', 'permissions']);

        if ($request->boolean('exclude_super_admin')) {
            $query->where('slug', '!=', 'super_admin');
        }

        $roles = $query->orderBy('name')->get();

        return response()->json($roles);
    }

    /**
     * Create a new role (Super Admin only). Assign permissions via permission_ids.
     *
     * @OA\Post(
     *     path="/super-admin/roles",
     *     tags={"Super Admin - Roles"},
     *     summary="Create Role",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","slug"},
     *         @OA\Property(property="name", type="string", example="Sales Manager"),
     *         @OA\Property(property="slug", type="string", example="sales_manager"),
     *         @OA\Property(property="description", type="string", nullable=true),
     *         @OA\Property(property="permission_ids", type="array", @OA\Items(type="integer"), description="IDs of permissions to assign")
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:roles,slug', 'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (! empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->loadCount(['users', 'permissions']);
        $role->load('permissions:id,name,slug,module');

        return response()->json($role, 201);
    }

    /**
     * Get a single role with its permissions (Super Admin only).
     *
     * @OA\Get(
     *     path="/super-admin/roles/{id}",
     *     tags={"Super Admin - Roles"},
     *     summary="Get Role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions:id,name,slug,module']);
        $role->loadCount('users');

        return response()->json($role);
    }

    /**
     * Update a role and/or its permissions (Super Admin only). System roles have restricted edits.
     *
     * @OA\Put(
     *     path="/super-admin/roles/{id}",
     *     tags={"Super Admin - Roles"},
     *     summary="Update Role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="description", type="string", nullable=true),
     *         @OA\Property(property="permission_ids", type="array", @OA\Items(type="integer"))
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system && $role->slug === 'super_admin') {
            return response()->json(['message' => 'Cannot modify super admin role.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:50', 'unique:roles,slug,' . $role->id, 'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($role->is_system && isset($validated['slug'])) {
            unset($validated['slug']);
        }

        $role->update(array_filter($validated, fn ($k) => ! in_array($k, ['permission_ids']), ARRAY_FILTER_USE_KEY));

        if (array_key_exists('permission_ids', $validated)) {
            $role->permissions()->sync($validated['permission_ids'] ?? []);
        }

        $role->load(['permissions:id,name,slug,module']);
        $role->loadCount('users');

        return response()->json($role);
    }

    /**
     * Delete a role (Super Admin only). Fails if role is system or has users assigned.
     *
     * @OA\Delete(
     *     path="/super-admin/roles/{id}",
     *     tags={"Super Admin - Roles"},
     *     summary="Delete Role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="No Content"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Cannot delete role with users")
     * )
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'Cannot delete system role.'], 403);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Cannot delete role with assigned users.'], 422);
        }

        $role->delete();

        return response()->json(null, 204);
    }
}
