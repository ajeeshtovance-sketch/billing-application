<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }

            return redirect()->route('super-admin.login');
        }

        if (! $user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Missing permission: ' . $permission], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
