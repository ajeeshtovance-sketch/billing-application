<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login - returns JWT token.
     * Accepts username or email for login.
     *
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="User Login",
     *     description="Authenticate with username or email and password. Returns JWT token. Use for all logins: organization users (admin, subadmin, manager, user, viewer) and Super Admin (same endpoint; use Super Admin credentials to get a token with role super_admin). ⚠️ Copy ONLY the `access_token` from the response for Authorization: Bearer {access_token}. Do NOT use JWT_SECRET from .env.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", description="Username or email for login", example="demo3"),
     *             @OA\Property(property="password", type="string", description="User password", example="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success - Copy access_token for authorization", @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string", description="JWT token - use in Authorization: Bearer header"),
     *         @OA\Property(property="token_type", type="string", example="bearer"),
     *         @OA\Property(property="expires_in", type="integer", description="Token expiration time in seconds")
     *     )),
     *     @OA\Response(response=422, description="Validation Error"),
     *     security={}
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = $request->username;
        $user  = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Register new organization and admin user (public).
     *
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register",
     *     description="Create a new organization and admin user. Returns JWT token.",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","email","password","password_confirmation","organization_name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="password_confirmation", type="string"),
     *         @OA\Property(property="organization_name", type="string"),
     *         @OA\Property(property="base_currency", type="string", example="INR")
     *     )),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string"),
     *         @OA\Property(property="token_type", type="string", example="bearer"),
     *         @OA\Property(property="expires_in", type="integer")
     *     )),
     *     @OA\Response(response=422, description="Validation Error"),
     *     security={}
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'          => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Organization::create([
            'name'          => $request->organization_name,
            'base_currency' => $request->input('base_currency', 'INR'),
        ]);

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'organization_id' => $organization->id,
            'role'            => 'admin',
            'status'          => 'active',
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Logout - invalidate JWT token.
     *
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout",
     *     description="Invalidate the current JWT token. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Successfully logged out")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh JWT token.
     *
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh Token",
     *     description="Get a new access token using current valid token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string"),
     *         @OA\Property(property="token_type", type="string", example="bearer"),
     *         @OA\Property(property="expires_in", type="integer")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Validate token format and structure (public endpoint for debugging).
     *
     * @OA\Post(
     *     path="/auth/validate-token",
     *     tags={"Authentication"},
     *     summary="Validate Token Format",
     *     description="Debug endpoint: Check if your token has the correct format. Send: Authorization: Bearer YOUR_TOKEN_HERE. If you accidentally send JWT_SECRET from .env, this endpoint will tell you and explain how to fix it.",
     *     @OA\Response(response=200, description="Valid token", @OA\JsonContent(
     *         @OA\Property(property="valid", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Token is valid"),
     *         @OA\Property(property="expires_in_seconds", type="integer")
     *     )),
     *     @OA\Response(response=400, description="Invalid or missing token", @OA\JsonContent(
     *         @OA\Property(property="valid", type="boolean", example=false),
     *         @OA\Property(property="message", type="string", example="Token is missing or invalid"),
     *         @OA\Property(property="error", type="string")
     *     )),
     *     security={}
     * )
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (! $token) {
                return response()->json([
                    'valid'            => false,
                    'message'          => 'Authorization header missing',
                    'error'            => 'Add Authorization header: Bearer YOUR_TOKEN_HERE',
                    'received_headers' => [
                        'Authorization' => $request->header('Authorization') ?: 'NOT SENT',
                    ],
                    'fix'              => '1. POST to /api/v1/auth/login with username and password\n2. Copy the access_token from response\n3. Add header: Authorization: Bearer {token}',
                ], 400);
            }

            if (strpos($token, 'JWT_SECRET=') !== false) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Invalid token format detected',
                    'error'   => '❌ You sent JWT_SECRET instead of actual token',
                    'fix'     => '1. POST /api/v1/auth/login with credentials\n2. Copy access_token from response\n3. Send: Authorization: Bearer {access_token}',
                ], 400);
            }

            // Parse and validate JWT token
            try {
                $jwtToken = auth('api')->setToken($token);

                if (! $jwtToken->check()) {
                    return response()->json([
                        'valid'   => false,
                        'message' => 'Token validation failed',
                        'error'   => 'Token is invalid, expired, or malformed',
                        'fix'     => 'Login again with POST /api/v1/auth/login to get a fresh token',
                    ], 401);
                }

                $user = $jwtToken->user();
                return response()->json([
                    'valid'              => true,
                    'message'            => '✅ Token is valid',
                    'user_id'            => $user->id,
                    'user_name'          => $user->name,
                    'user_email'         => $user->email,
                    'expires_in_seconds' => auth('api')->factory()->getTTL() * 60,
                    'expires_in_minutes' => auth('api')->factory()->getTTL(),
                ]);
            } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Token has expired',
                    'error'   => $e->getMessage(),
                    'fix'     => 'Login again with POST /api/v1/auth/login to get a fresh token',
                ], 401);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Token is invalid',
                    'error'   => $e->getMessage(),
                    'fix'     => 'Verify your token format and ensure it starts with eyJ',
                ], 401);
            } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'JWT error',
                    'error'   => $e->getMessage(),
                    'fix'     => 'Ensure JWT_SECRET is properly configured in .env',
                ], 401);
            }

        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Token validation error',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get authenticated user profile.
     *
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="User Profile",
     *     description="Get the authenticated user's profile. Requires Bearer token obtained from POST /api/v1/auth/login. ⚠️ Use ONLY the `access_token` returned by the login response as the Bearer token (e.g. Authorization: Bearer {access_token}). Do NOT use the JWT_SECRET from .env.\n\nExample curl:\n\ncurl --location 'http://127.0.0.1:8000/api/v1/auth/me' \\\n  --header 'accept: application/json' \\\n  --header 'Authorization: Bearer {access_token}'",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="John Doe"),
     *         @OA\Property(property="email", type="string", nullable=true),
     *         @OA\Property(property="username", type="string", nullable=true),
     *         @OA\Property(property="role", type="string", example="user"),
     *         @OA\Property(property="organization_id", type="integer", nullable=true),
     *         @OA\Property(property="organization", type="object", description="Organization details when applicable")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load('organization');

        return response()->json($user);
    }

    /**
     * Get current user's permissions and enabled menus (by module). Use for role-based UI: show only menus the user has permission to access.
     *
     * @OA\Get(
     *     path="/auth/permissions",
     *     tags={"Authentication"},
     *     summary="My Permissions & Menus",
     *     description="Returns the authenticated user's permissions and enabled menu modules. Use this to show/hide menus based on role. Super admin gets all permissions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="module", type="string", nullable=true)
     *         )),
     *         @OA\Property(property="menus", type="array", @OA\Items(type="string"), description="Unique module names the user can access (for showing enabled menus)")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function permissions(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->role === 'super_admin') {
            $permissions = \App\Models\Permission::orderBy('module')->orderBy('name')->get(['id', 'name', 'slug', 'module']);
            $menus = $permissions->pluck('module')->unique()->filter()->values()->toArray();
            return response()->json([
                'permissions' => $permissions,
                'menus' => array_values($menus),
            ]);
        }

        $role = $user->roleModel;
        if (! $role) {
            return response()->json(['permissions' => [], 'menus' => []]);
        }

        $role->load('permissions:id,name,slug,module');
        $permissions = $role->permissions;
        $menus = $permissions->pluck('module')->unique()->filter()->values()->toArray();

        return response()->json([
            'permissions' => $permissions,
            'menus' => array_values($menus),
        ]);
    }

    /**
     * Dashboard summary (sales, paid, unpaid, cancelled for period).
     *
     * @OA\Get(
     *     path="/dashboard/summary",
     *     tags={"Dashboard"},
     *     summary="Dashboard Summary",
     *     description="Total sales, paid, unpaid, cancelled for organization by period (today, week, month, year).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"today","week","month","year"}, default="month")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="total_sales", type="number"),
     *         @OA\Property(property="paid", type="number"),
     *         @OA\Property(property="unpaid", type="number"),
     *         @OA\Property(property="cancelled", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function dashboardSummary(Request $request): JsonResponse
    {
        $user           = auth('api')->user();
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            return response()->json([
                'total_sales' => 0,
                'paid'        => 0,
                'unpaid'      => 0,
                'cancelled'   => 0,
            ]);
        }

        $period = $request->input('period', 'month'); // today, week, month, year

        $query = \App\Models\Invoice::where('organization_id', $organizationId);

        switch ($period) {
            case 'today':
                $query->whereDate('issue_date', today());
                break;
            case 'week':
                $query->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year);
                break;
            case 'year':
                $query->whereYear('issue_date', now()->year);
                break;
        }

        $invoices = $query->get();

        $totalSales = $invoices->whereNotIn('status', ['cancelled'])->sum('total');
        $paid       = $invoices->where('status', 'paid')->sum('amount_paid');
        $unpaid     = $invoices->whereIn('status', ['sent', 'unpaid', 'partial'])->sum('balance_due');
        $cancelled  = $invoices->where('status', 'cancelled')->sum('total');

        return response()->json([
            'total_sales' => round($totalSales, 2),
            'paid'        => round($paid, 2),
            'unpaid'      => round($unpaid, 2),
            'cancelled'   => round($cancelled, 2),
            'period'      => $period,
        ]);
    }

    /**
     * Format token response.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
