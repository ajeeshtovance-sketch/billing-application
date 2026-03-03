<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * List all permissions, optionally grouped by module.
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
