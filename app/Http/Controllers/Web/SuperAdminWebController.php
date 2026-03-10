<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdminWebController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'super_admin') {
            return redirect()->route('super-admin.dashboard');
        }

        return view('super-admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role !== 'super_admin') {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => ['Access denied. Super admin only.'],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('super-admin.dashboard'));
    }

    public function dashboard(Request $request): View
    {
        $period = $request->input('period', 'month');

        $organizations = Organization::query();
        $totalOrgs = $organizations->count();
        $activeOrgs = (clone $organizations)->where('status', 'active')->count();

        $totalUsers = User::whereNotNull('organization_id')->count();

        $invoiceQuery = Invoice::query();
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

        $recentOrgs = Organization::withCount('users')->latest()->take(5)->get();

        return view('super-admin.dashboard', [
            'stats' => [
                'total_organizations' => $totalOrgs,
                'active_organizations' => $activeOrgs,
                'total_users' => $totalUsers,
                'total_sales' => round($totalSales, 2),
                'paid' => round($paid, 2),
                'unpaid' => round($unpaid, 2),
                'period' => $period,
            ],
            'recent_organizations' => $recentOrgs,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }

    public function rolesIndex(): View
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('super-admin.roles.index', ['roles' => $roles]);
    }

    public function rolesCreate(): View
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('super-admin.roles.form', [
            'role' => null,
            'permissions' => $permissions,
        ]);
    }

    public function rolesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:roles,slug', 'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('super-admin.roles.index')->with('success', 'Role created.');
    }

    public function rolesEdit(Role $role): View|RedirectResponse
    {
        if ($role->slug === 'super_admin') {
            return redirect()->route('super-admin.roles.index')->withErrors(['message' => 'Cannot edit super admin role.']);
        }

        $role->load('permissions');
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('super-admin.roles.form', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function rolesUpdate(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system && $role->slug === 'super_admin') {
            return redirect()->route('super-admin.roles.index')->withErrors(['message' => 'Cannot modify super admin role.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:50', 'unique:roles,slug,' . $role->id, 'regex:/^[a-z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($role->is_system) {
            unset($validated['slug']);
        }

        $role->update(array_filter($validated, fn ($k) => $k !== 'permissions', ARRAY_FILTER_USE_KEY));

        if (array_key_exists('permissions', $validated)) {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        return redirect()->route('super-admin.roles.index')->with('success', 'Role updated.');
    }

    public function rolesDestroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->route('super-admin.roles.index')->withErrors(['message' => 'Cannot delete system role.']);
        }

        if ($role->users()->exists()) {
            return redirect()->route('super-admin.roles.index')->withErrors(['message' => 'Cannot delete role with assigned users.']);
        }

        $role->delete();

        return redirect()->route('super-admin.roles.index')->with('success', 'Role deleted.');
    }

    public function permissionsIndex(): View
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('super-admin.permissions.index', ['permissions' => $permissions]);
    }

    public function organizationsIndex(Request $request): View
    {
        $query = Organization::query()->withCount('users');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $organizations = $query->latest()->paginate(15)->withQueryString();

        return view('super-admin.organizations.index', ['organizations' => $organizations]);
    }

    public function organizationsCreate(): View
    {
        $roles = Role::whereIn('slug', ['admin', 'subadmin', 'manager', 'user', 'viewer'])->orderBy('name')->get();

        return view('super-admin.organizations.form', [
            'organization' => null,
            'roles' => $roles,
        ]);
    }

    public function organizationsStore(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,suspended,trial'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'string', 'email', 'max:255'],
            'admin_password' => ['nullable', 'confirmed', Password::defaults()],
            'admin_role' => ['nullable', 'string', 'in:admin,subadmin,manager,user,viewer'],
        ];

        if ($request->filled('admin_email')) {
            $rules['admin_name'] = ['required', 'string', 'max:255'];
            $rules['admin_email'] = ['required', 'string', 'email', 'max:255', 'unique:users,email'];
            $rules['admin_password'] = ['required', 'confirmed', Password::defaults()];
            $rules['admin_role'] = ['required', 'string', 'in:admin,subadmin,manager,user,viewer'];
        }

        $validated = $request->validate($rules);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['base_currency'] = $validated['base_currency'] ?? 'INR';

        $organization = Organization::create([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'base_currency' => $validated['base_currency'] ?? 'INR',
            'status' => $validated['status'] ?? 'active',
            'user_limit' => $validated['user_limit'] ?? null,
        ]);

        if (! empty($validated['admin_email'])) {
            $userLimit = $validated['user_limit'] ?? null;
            if ($userLimit !== null && 1 > $userLimit) {
                return redirect()->back()->withErrors(['user_limit' => 'User limit must be at least 1 to add admin.']);
            }

            $roleModel = Role::where('slug', $validated['admin_role'] ?? 'admin')->first();

            User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'organization_id' => $organization->id,
                'role_id' => $roleModel?->id,
                'role' => $validated['admin_role'] ?? 'admin',
                'status' => 'active',
            ]);
        }

        $message = 'Organization created.';
        if (! empty($validated['admin_email'])) {
            $message .= ' Admin can log in at /admin/login with the email and password you set.';
        }

        return redirect()->route('super-admin.organizations.index')->with('success', $message);
    }

    public function organizationsEdit(Organization $organization): View
    {
        $organization->loadCount('users');
        $organization->load(['users' => fn ($q) => $q->orderBy('name')]);
        $roles = Role::whereIn('slug', ['admin', 'subadmin', 'manager', 'user', 'viewer'])->orderBy('name')->get();

        return view('super-admin.organizations.form', [
            'organization' => $organization,
            'roles' => $roles,
        ]);
    }

    public function organizationsAddUser(Request $request, Organization $organization): RedirectResponse
    {
        $userLimit = $organization->user_limit;
        $currentCount = $organization->users()->count();
        if ($userLimit !== null && $currentCount >= $userLimit) {
            return redirect()->route('super-admin.organizations.edit', $organization)
                ->withErrors(['message' => "User limit reached ({$userLimit} users). Increase the limit in organization settings."]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,subadmin,manager,user,viewer'],
        ]);

        $roleModel = Role::where('slug', $validated['role'])->first();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_id' => $organization->id,
            'role_id' => $roleModel?->id,
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        return redirect()
            ->route('super-admin.organizations.edit', $organization)
            ->with('success', 'User created. They can log in at /admin/login with this email and password.');
    }

    public function organizationsUpdate(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,suspended,trial'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $userLimit = $validated['user_limit'] ?? null;
        if ($userLimit !== null && $organization->users()->count() > $userLimit) {
            return redirect()->back()->withErrors(['user_limit' => "Cannot set limit below current user count ({$organization->users()->count()} users)."]);
        }

        $organization->update($validated);

        return redirect()->route('super-admin.organizations.index')->with('success', 'Organization updated.');
    }

    public function organizationsDestroy(Organization $organization): RedirectResponse
    {
        if ($organization->users()->exists()) {
            return redirect()->route('super-admin.organizations.index')->withErrors(['message' => 'Cannot delete organization with users. Remove or reassign users first.']);
        }

        $organization->delete();

        return redirect()->route('super-admin.organizations.index')->with('success', 'Organization deleted.');
    }
}
