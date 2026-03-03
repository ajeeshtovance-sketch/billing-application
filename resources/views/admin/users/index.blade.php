@extends('admin.layout')

@section('title', 'User Management')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">User Management</h1>
    <p class="text-slate-600 mb-6">Manage team users for {{ $organization->name }}. {{ $userCount }}{{ $userLimit ? " / {$userLimit}" : '' }} users.</p>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            @foreach ($errors->all() as $error)
                <p class="text-red-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Users List</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $userCount }}{{ $userLimit ? " / {$userLimit}" : '' }} users
                    @if ($atLimit)
                        <span class="text-amber-600 font-medium">— Limit reached</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[400px]">
                <thead class="bg-slate-50 text-left text-sm text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Username / Email</th>
                        <th class="px-6 py-3 font-medium">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $u)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $u->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $u->username ?? $u->email }}</td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $u->role }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-500">No users yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canAddUsers && !$atLimit)
            <div class="p-4 sm:p-6 border-t border-slate-200 bg-slate-50/50">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Add User / Onboard</h3>
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
                                placeholder="Full name">
                        </div>
                        <div>
                            <label for="login" class="block text-sm font-medium text-slate-700 mb-1">Username or Email</label>
                            <input type="text" name="login" id="login" value="{{ old('login') }}" required
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
                                placeholder="demo3 or user@example.com">
                            <p class="text-xs text-slate-500 mt-1">For login — use username (e.g. demo3) or email</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" id="password" required
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
                                placeholder="••••••••">
                            <p class="text-xs text-slate-500 mt-1">For login</p>
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
                                placeholder="••••••••">
                        </div>
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                        <select name="role" id="role" required
                            class="w-full sm:max-w-xs px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                            @foreach ($roles as $r)
                                <option value="{{ $r->slug }}" {{ old('role', 'user') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Default: User</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition">
                        Add User
                    </button>
                </form>
            </div>
        @elseif ($atLimit)
            <div class="p-4 sm:p-6 border-t border-slate-200 bg-amber-50/50">
                <p class="text-sm text-amber-800">User limit ({{ $userLimit }}) reached. Contact super admin to increase your organization's user limit.</p>
            </div>
        @endif
    </div>
</div>
@endsection
