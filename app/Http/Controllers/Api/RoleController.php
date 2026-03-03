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
     * List all roles.
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
     * Store a new role.
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
     * Show a role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions:id,name,slug,module']);
        $role->loadCount('users');

        return response()->json($role);
    }

    /**
     * Update a role.
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
     * Delete a role.
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
