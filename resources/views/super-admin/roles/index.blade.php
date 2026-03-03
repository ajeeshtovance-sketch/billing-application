@extends('super-admin.layout')

@section('title', 'Roles')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Roles & Permissions</h1>
            <a href="{{ route('super-admin.roles.create') }}"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                Add Role
            </a>
        </div>

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

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-slate-50 text-left text-sm text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Slug</th>
                        <th class="px-6 py-3 font-medium">Users</th>
                        <th class="px-6 py-3 font-medium">Permissions</th>
                        <th class="px-6 py-3 font-medium">System</th>
                        <th class="px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $role->name }}</td>
                            <td class="px-6 py-4 text-slate-600 font-mono text-sm">{{ $role->slug }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $role->users_count }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $role->permissions_count }}</td>
                            <td class="px-6 py-4">
                                @if ($role->is_system)
                                    <span class="text-xs text-slate-500">Yes</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                @if ($role->slug !== 'super_admin')
                                    <a href="{{ route('super-admin.roles.edit', $role) }}"
                                        class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Edit</a>
                                    @if (!$role->is_system && $role->users_count === 0)
                                        <form method="POST" action="{{ route('super-admin.roles.destroy', $role) }}"
                                            class="inline" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                                        </form>
                                    @endif
                                @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('super-admin.permissions.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                View all permissions →
            </a>
        </div>
</div>
@endsection
