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
     *     description="Authenticate with username or email and password. Returns JWT token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", description="Username or email for login", example="demo3"),
     *             @OA\Property(property="password", type="string", description="User password", example="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string"),
     *         @OA\Property(property="token_type", type="string", example="bearer"),
     *         @OA\Property(property="expires_in", type="integer")
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
        $user = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Organization::create([
            'name' => $request->organization_name,
            'base_currency' => $request->input('base_currency', 'INR'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $organization->id,
            'role' => 'admin',
            'status' => 'active',
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

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get authenticated user profile.
     *
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="User Profile",
     *     description="Get the authenticated user's profile. Requires Bearer token.",
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

    public function dashboardSummary(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            return response()->json([
                'total_sales' => 0,
                'paid' => 0,
                'unpaid' => 0,
                'cancelled' => 0,
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
        $paid = $invoices->where('status', 'paid')->sum('amount_paid');
        $unpaid = $invoices->whereIn('status', ['sent', 'unpaid', 'partial'])->sum('balance_due');
        $cancelled = $invoices->where('status', 'cancelled')->sum('total');

        return response()->json([
            'total_sales' => round($totalSales, 2),
            'paid' => round($paid, 2),
            'unpaid' => round($unpaid, 2),
            'cancelled' => round($cancelled, 2),
            'period' => $period,
        ]);
    }

    /**
     * Format token response.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
