@extends('super-admin.layout')

@section('title', 'Permissions')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto w-full">
        <div class="mb-6">
            <a href="{{ route('super-admin.roles.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">← Back to roles</a>
        </div>

        <h1 class="text-2xl font-bold text-slate-800 mb-6">All Permissions</h1>

        <div class="space-y-6">
            @forelse ($permissions as $module => $perms)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-3 bg-slate-50 border-b border-slate-200">
                        <h2 class="font-medium text-slate-700 capitalize">{{ $module ?: 'General' }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($perms as $perm)
                                <div class="flex justify-between items-start p-3 rounded-lg bg-slate-50">
                                    <div>
                                        <p class="font-medium text-slate-800 text-sm">{{ $perm->name }}</p>
                                        <p class="text-xs text-slate-500 font-mono">{{ $perm->slug }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">No permissions found.</div>
            @endforelse
        </div>
</div>
@endsection
