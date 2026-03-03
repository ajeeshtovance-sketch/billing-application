<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user->role === 'super_admin') {
                return redirect()->route('super-admin.dashboard');
            }
            if ($user->organization_id) {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = $request->login;
        $user = \App\Models\User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role === 'super_admin') {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => ['Super admins must use the Super Admin portal.'],
            ]);
        }

        if (! $user->organization_id) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => ['Access denied. No organization assigned.'],
            ]);
        }

        $org = Organization::find($user->organization_id);
        if (! $org || $org->status !== 'active') {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => ['Organization is not active. Contact support.'],
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function dashboard(Request $request): View
    {
        $user = Auth::guard('web')->user();
        $org = $user->organization;

        $period = $request->input('period', 'month');
        $invoiceQuery = Invoice::where('organization_id', $user->organization_id);

        switch ($period) {
            case 'today':
                $invoiceQuery->whereDate('issue_date', today());
                break;
            case 'week':
                $invoiceQuery->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $invoiceQuery->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year);
                break;
            case 'year':
                $invoiceQuery->whereYear('issue_date', now()->year);
                break;
        }

        $invoices = $invoiceQuery->get();
        $totalSales = $invoices->whereNotIn('status', ['cancelled'])->sum('total');
        $paid = $invoices->where('status', 'paid')->sum('amount_paid');
        $unpaid = $invoices->whereIn('status', ['sent', 'unpaid', 'partial'])->sum('balance_due');

        $orgUsers = User::where('organization_id', $user->organization_id)->orderBy('name')->get();
        $roles = Role::whereIn('slug', ['admin', 'subadmin', 'manager', 'user', 'viewer'])->orderBy('name')->get();
        $canAddUsers = in_array($user->role, ['admin', 'subadmin']);
        $userLimit = $org->user_limit;
        $userCount = $orgUsers->count();
        $atLimit = $userLimit !== null && $userCount >= $userLimit;

        return view('admin.dashboard', [
            'organization' => $org,
            'user' => $user,
            'userCount' => $userCount,
            'userLimit' => $userLimit,
            'stats' => [
                'total_sales' => round($totalSales, 2),
                'paid' => round($paid, 2),
                'unpaid' => round($unpaid, 2),
                'period' => $period,
            ],
        ]);
    }

    public function usersIndex(): View
    {
        $user = Auth::guard('web')->user();
        $org = $user->organization;
        $orgUsers = User::where('organization_id', $user->organization_id)->orderBy('name')->get();
        $roles = Role::whereIn('slug', ['admin', 'subadmin', 'manager', 'user', 'viewer'])->orderBy('name')->get();
        $canAddUsers = in_array($user->role, ['admin', 'subadmin']);
        $userLimit = $org->user_limit;
        $userCount = $orgUsers->count();
        $atLimit = $userLimit !== null && $userCount >= $userLimit;

        return view('admin.users.index', [
            'organization' => $org,
            'users' => $orgUsers,
            'roles' => $roles,
            'canAddUsers' => $canAddUsers,
            'userLimit' => $userLimit,
            'userCount' => $userCount,
            'atLimit' => $atLimit,
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! in_array($user->role, ['admin', 'subadmin'])) {
            return redirect()->route('admin.users.index')->withErrors(['message' => 'You do not have permission to add users.']);
        }

        $org = $user->organization;
        $userLimit = $org->user_limit;
        $currentCount = User::where('organization_id', $org->id)->count();

        if ($userLimit !== null && $currentCount >= $userLimit) {
            return redirect()->route('admin.users.index')
                ->withErrors(['message' => "User limit reached ({$userLimit} users). Contact super admin to increase limit."]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,subadmin,manager,user,viewer'],
        ]);

        $login = trim($validated['login']);
        $isEmail = str_contains($login, '@');

        if ($isEmail) {
            $request->validate([
                'login' => ['email', 'unique:users,email'],
            ]);
            $email = $login;
            $username = $login;
        } else {
            $request->validate([
                'login' => ['regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
            ], [
                'login.regex' => 'Username may only contain letters, numbers, dots, underscores and hyphens.',
            ]);
            $username = $login;
            $email = null;
        }

        $role = $validated['role'] ?? 'user';
        $roleModel = Role::where('slug', $role)->first();

        User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'organization_id' => $org->id,
            'role_id' => $roleModel?->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User added. They can log in at /admin/login.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
