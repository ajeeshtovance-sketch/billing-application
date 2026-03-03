<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationAdminMiddleware
{
    /**
     * Allow super_admin or organization admin (role = admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if (in_array($user->role, ['super_admin', 'admin', 'subadmin'])) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
    }
}
