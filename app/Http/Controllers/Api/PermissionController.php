<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * List all permissions (Super Admin only). Use when creating/editing roles to assign permissions. Optionally filter by module or return grouped by module for menu structure.
     *
     * @OA\Get(
     *     path="/super-admin/permissions",
     *     tags={"Super Admin - Permissions"},
     *     summary="List Permissions",
     *     description="List all permissions. Use for role-based access: assign permission_ids to roles; users get menus based on their role's permissions (see GET /auth/permissions).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="module", in="query", description="Filter by module (e.g. invoices, customers, users)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="grouped", in="query", description="If true, return permissions grouped by module", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="array", @OA\Items(type="object",
     *         @OA\Property(property="id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="module", type="string", nullable=true),
     *         @OA\Property(property="description", type="string", nullable=true)
     *     ))),
     *     @OA\Response(response=403, description="Forbidden - Super Admin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()->orderBy('module')->orderBy('name');

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        $permissions = $query->get();

        if ($request->boolean('grouped')) {
            $grouped = $permissions->groupBy('module')->map(fn ($items) => $items->values());
            return response()->json($grouped);
        }

        return response()->json($permissions);
    }
}
