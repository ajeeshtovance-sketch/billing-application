@extends('super-admin.layout')

@section('title', 'Organizations')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Organizations</h1>
        <a href="{{ route('super-admin.organizations.create') }}"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition shrink-0">
            Add Organization
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

    {{-- Status filter --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('super-admin.organizations.index') }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            All
        </a>
        @foreach (['active' => 'Active', 'suspended' => 'Suspended', 'trial' => 'Trial'] as $s => $label)
            <a href="{{ route('super-admin.organizations.index', ['status' => $s]) }}"
                class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request('status') === $s ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-slate-50 text-left text-sm text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Legal Name</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Users / Limit</th>
                        <th class="px-6 py-3 font-medium">Currency</th>
                        <th class="px-6 py-3 font-medium">Created</th>
                        <th class="px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($organizations as $org)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $org->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $org->legal_name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                    {{ $org->status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $org->status === 'suspended' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $org->status === 'trial' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ !in_array($org->status, ['active','suspended','trial']) ? 'bg-slate-100 text-slate-600' : '' }}">
                                    {{ $org->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $org->users_count }}{{ $org->user_limit ? " / {$org->user_limit}" : '' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $org->base_currency }}</td>
                            <td class="px-6 py-4 text-slate-500 text-sm">{{ $org->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('super-admin.organizations.edit', $org) }}"
                                        class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Edit</a>
                                    @if ($org->users_count === 0)
                                        <form method="POST" action="{{ route('super-admin.organizations.destroy', $org) }}"
                                            class="inline" onsubmit="return confirm('Delete this organization? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">No organizations found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($organizations->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $organizations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
