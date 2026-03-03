<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List users. Super Admin: all org users. Org Admin: users in their org only.
     *
     * @OA\Get(
     *     path="/admin/users",
     *     tags={"Admin - Users"},
     *     summary="List Users",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="organization_id", in="query", description="Filter by org (super_admin only)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="role", in="query", @OA\Schema(type="string", enum={"admin","manager","user","viewer"})),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = User::query()->with('organization:id,name,status');

        if ($user->role === 'super_admin') {
            if ($orgId = $request->input('organization_id')) {
                $query->where('organization_id', $orgId);
            }
            // Exclude super_admin from list
            $query->where('role', '!=', 'super_admin');
        } else {
            $query->where('organization_id', $user->organization_id);
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $users = $query->latest()->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Create user. Super Admin: can assign org. Org Admin: adds to their org.
     *
     * @OA\Post(
     *     path="/admin/users",
     *     tags={"Admin - Users"},
     *     summary="Create User",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","email","password","password_confirmation","role"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string"),
     *         @OA\Property(property="role", type="string", enum={"admin","manager","user","viewer"}),
     *         @OA\Property(property="organization_id", type="integer", description="Required for super_admin")
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = auth('api')->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,subadmin,manager,user,viewer'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];

        if ($currentUser->role === 'super_admin') {
            $rules['organization_id'] = ['required', 'integer', 'exists:organizations,id'];
        }

        $validated = $request->validate($rules);

        $organizationId = $currentUser->role === 'super_admin'
            ? $validated['organization_id']
            : $currentUser->organization_id;

        $org = Organization::find($organizationId);
        $userLimit = $org?->user_limit;
        if ($userLimit !== null && $org->users()->count() >= $userLimit) {
            return response()->json(['message' => "User limit reached ({$userLimit} users) for this organization."], 403);
        }

        // Org admin/subadmin: ensure org is active
        if (in_array($currentUser->role, ['admin', 'subadmin'])) {
            if (! $org || $org->status !== 'active') {
                return response()->json(['message' => 'Organization is not active.'], 403);
            }
        }

        $roleModel = Role::where('slug', $validated['role'])->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_id' => $organizationId,
            'role_id' => $roleModel?->id,
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'active',
        ]);

        $user->load('organization:id,name');

        return response()->json($user, 201);
    }

    /**
     * Show user.
     *
     * @OA\Get(
     *     path="/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Get User",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(User $user): JsonResponse
    {
        $currentUser = auth('api')->user();

        if ($currentUser->role !== 'super_admin' && $user->organization_id !== $currentUser->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->role === 'super_admin') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $user->load('organization:id,name,status');

        return response()->json($user);
    }

    /**
     * Update user.
     *
     * @OA\Put(
     *     path="/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Update User",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="role", type="string", enum={"admin","manager","user","viewer"}),
     *         @OA\Property(property="status", type="string", enum={"active","inactive"}),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string"),
     *         @OA\Property(property="organization_id", type="integer", description="Super admin only")
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $currentUser = auth('api')->user();

        if ($currentUser->role !== 'super_admin' && $user->organization_id !== $currentUser->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->role === 'super_admin') {
            return response()->json(['message' => 'Cannot modify super admin.'], 403);
        }

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'in:admin,subadmin,manager,user,viewer'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];

        if ($currentUser->role === 'super_admin') {
            $rules['organization_id'] = ['sometimes', 'integer', 'exists:organizations,id'];
        }

        $validated = $request->validate($rules);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (isset($validated['role'])) {
            $roleModel = Role::where('slug', $validated['role'])->first();
            $validated['role_id'] = $roleModel?->id;
        }

        $user->update($validated);

        $user->load('organization:id,name');

        return response()->json($user);
    }

    /**
     * Delete user.
     *
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Delete User",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="No Content"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        $currentUser = auth('api')->user();

        if ($currentUser->role !== 'super_admin' && $user->organization_id !== $currentUser->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->role === 'super_admin') {
            return response()->json(['message' => 'Cannot delete super admin.'], 403);
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Cannot delete yourself.'], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
