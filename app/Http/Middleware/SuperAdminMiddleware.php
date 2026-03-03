<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user || $user->role !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized. Super admin access required.'], 403);
        }

        return $next($request);
    }
}
