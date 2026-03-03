@extends('super-admin.layout')

@section('title', $organization ? 'Edit Organization' : 'Create Organization')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-3xl mx-auto w-full">
    <div class="mb-6">
        <a href="{{ route('super-admin.organizations.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">← Back to organizations</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h1 class="text-xl font-bold text-slate-800 mb-6">{{ $organization ? 'Edit Organization' : 'Create Organization' }}</h1>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($organization)
            <p class="mb-4 text-sm text-slate-500">{{ $organization->users_count }} user(s) in this organization</p>
        @endif

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ $organization ? route('super-admin.organizations.update', $organization) : route('super-admin.organizations.store') }}">
            @csrf
            @if ($organization) @method('PUT') @endif

            <div class="space-y-4 mb-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $organization?->name) }}" required
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Acme Corp">
                </div>
                <div>
                    <label for="legal_name" class="block text-sm font-medium text-slate-700 mb-1">Legal Name</label>
                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $organization?->legal_name) }}"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Acme Corporation Pvt Ltd">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="base_currency" class="block text-sm font-medium text-slate-700 mb-1">Base Currency</label>
                        <input type="text" name="base_currency" id="base_currency" value="{{ old('base_currency', $organization?->base_currency ?? 'INR') }}"
                            maxlength="3"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="INR">
                        <p class="text-xs text-slate-500 mt-1">3-letter code (e.g. INR, USD)</p>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select name="status" id="status"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active" {{ old('status', $organization?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $organization?->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="trial" {{ old('status', $organization?->status) === 'trial' ? 'selected' : '' }}>Trial</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="user_limit" class="block text-sm font-medium text-slate-700 mb-1">User Limit</label>
                    <input type="number" name="user_limit" id="user_limit" min="1" step="1" value="{{ old('user_limit', $organization?->user_limit) }}"
                        class="w-full sm:max-w-xs px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Unlimited">
                    <p class="text-xs text-slate-500 mt-1">Max users allowed (e.g. 5). Leave empty for unlimited.</p>
                </div>

                @if (!$organization)
                <hr class="my-6 border-slate-200">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Create first admin user (optional)</h3>
                <p class="text-xs text-slate-500 mb-4">Add login credentials so the sub-admin can log in at /admin/login right away.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1">Admin Name</label>
                        <input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="John Doe">
                    </div>
                    <div>
                        <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1">Admin Email</label>
                        <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="admin@example.com">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="admin_password" id="admin_password"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="••••••••">
                    </div>
                    <div>
                        <label for="admin_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" id="admin_password_confirmation"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="••••••••">
                    </div>
                </div>
                <div class="mt-4">
                    <label for="admin_role" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                    <select name="admin_role" id="admin_role"
                        class="w-full sm:max-w-xs px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($roles ?? [] as $r)
                            <option value="{{ $r->slug }}" {{ old('admin_role', 'subadmin') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                    {{ $organization ? 'Update' : 'Create' }} Organization
                </button>
                <a href="{{ route('super-admin.organizations.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

        @if ($organization)
            <hr class="my-8 border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Users in this organization</h2>
            <div class="overflow-x-auto mb-6">
                <table class="w-full min-w-[400px] text-sm">
                    <thead class="text-left text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="pb-2 font-medium">Name</th>
                            <th class="pb-2 font-medium">Email</th>
                            <th class="pb-2 font-medium">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($organization->users as $u)
                            <tr>
                                <td class="py-2 text-slate-800">{{ $u->name }}</td>
                                <td class="py-2 text-slate-600">{{ $u->email }}</td>
                                <td class="py-2"><span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $u->role }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-slate-500">No users yet. Add a sub-admin below.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <h3 class="text-sm font-medium text-slate-700 mb-3">Add Sub-Admin / User</h3>
            <form method="POST" action="{{ route('super-admin.organizations.add-user', $organization) }}" class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="user_name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input type="text" name="name" id="user_name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="user_email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" id="user_email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="user_password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" id="user_password" required
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="user_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="user_password_confirmation" required
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label for="user_role" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                    <select name="role" id="user_role" required
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                        @foreach ($roles ?? [] as $r)
                            <option value="{{ $r->slug }}" {{ old('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Use "Sub Admin" or "Admin" for organization administrators</p>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition">
                    Add User
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
