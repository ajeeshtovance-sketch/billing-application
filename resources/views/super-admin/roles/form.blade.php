@extends('super-admin.layout')

@section('title', $role ? 'Edit Role' : 'Create Role')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-3xl mx-auto w-full">
        <div class="mb-6">
            <a href="{{ route('super-admin.roles.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">← Back to roles</a>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h1 class="text-xl font-bold text-slate-800 mb-6">{{ $role ? 'Edit Role' : 'Create Role' }}</h1>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-700 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ $role ? route('super-admin.roles.update', $role) : route('super-admin.roles.store') }}">
                @csrf
                @if ($role) @method('PUT') @endif

                <div class="space-y-4 mb-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $role?->name) }}" required
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $role?->slug) }}"
                            {{ $role?->is_system ? 'readonly' : '' }}
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $role?->is_system ? 'bg-slate-100' : '' }}"
                            placeholder="e.g. sales_manager">
                        <p class="text-xs text-slate-500 mt-1">Lowercase letters, numbers, underscores only</p>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $role?->description) }}"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-slate-700">Permissions</label>
                        <button type="button" onclick="document.querySelectorAll('.perm-checkbox').forEach(c => c.checked = true)" class="text-xs text-indigo-600 hover:text-indigo-700">Select all</button>
                        <button type="button" onclick="document.querySelectorAll('.perm-checkbox').forEach(c => c.checked = false)" class="text-xs text-slate-500 hover:text-slate-700">Clear</button>
                    </div>
                    <div class="border border-slate-200 rounded-lg p-4 max-h-80 overflow-y-auto space-y-4">
                        @forelse ($permissions as $module => $perms)
                            <div>
                                <p class="text-sm font-medium text-slate-700 mb-2 capitalize">{{ $module ?: 'General' }}</p>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($perms as $perm)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                class="perm-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                {{ in_array($perm->id, old('permissions', $role?->permissions->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
                                            <span class="text-sm text-slate-600">{{ $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No permissions available.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                        {{ $role ? 'Update' : 'Create' }} Role
                    </button>
                    <a href="{{ route('super-admin.roles.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
</div>
@endsection
