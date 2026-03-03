<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrgAdminWebMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::guard('web')->user();

        if ($user->role === 'super_admin') {
            return redirect()->route('super-admin.dashboard');
        }

        if (! $user->organization_id) {
            Auth::guard('web')->logout();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Access denied. No organization assigned.']);
        }

        return $next($request);
    }
}
