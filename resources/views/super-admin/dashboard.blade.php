@extends('super-admin.layout')

@section('title', 'Dashboard')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
        {{-- Period filter --}}
        <div class="mb-6 flex flex-wrap gap-2">
            @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $p => $label)
                <a href="{{ route('super-admin.dashboard', ['period' => $p]) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $stats['period'] === $p ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Organizations</p>
                <p class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_organizations'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Active Orgs</p>
                <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">{{ $stats['active_organizations'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Total Users</p>
                <p class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_users'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Total Sales</p>
                <p class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">{{ number_format($stats['total_sales'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Paid</p>
                <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['paid'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">Unpaid</p>
                <p class="text-xl sm:text-2xl font-bold text-amber-600 mt-1">{{ number_format($stats['unpaid'], 2) }}</p>
            </div>
        </div>

        {{-- Recent organizations --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Recent Organizations</h2>
            </div>
            <div class="overflow-x-auto -mx-px">
                <table class="w-full min-w-[500px]">
                    <thead class="bg-slate-50 text-left text-sm text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Name</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Users</th>
                            <th class="px-6 py-3 font-medium">Currency</th>
                            <th class="px-6 py-3 font-medium">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($recent_organizations as $org)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-medium text-slate-800">{{ $org->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $org->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $org->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $org->users_count }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $org->base_currency }}</td>
                                <td class="px-6 py-4 text-slate-500 text-sm">{{ $org->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">No organizations yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>
@endsection
