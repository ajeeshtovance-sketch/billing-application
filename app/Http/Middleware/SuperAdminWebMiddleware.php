<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminWebMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('super-admin.login');
        }

        if (Auth::guard('web')->user()->role !== 'super_admin') {
            Auth::guard('web')->logout();

            return redirect()->route('super-admin.login')
                ->withErrors(['email' => 'Access denied. Super admin only.']);
        }

        return $next($request);
    }
}
