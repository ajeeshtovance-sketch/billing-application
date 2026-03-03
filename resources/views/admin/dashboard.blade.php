@extends('admin.layout')

@section('title', 'Dashboard')
@section('sidebar', true)

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Dashboard</h1>
    <p class="text-slate-600 mb-6">Welcome, {{ $user->name }}. You are logged in as <span class="font-medium">{{ $user->role }}</span> for {{ $organization->name }}.</p>

    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            User Management — {{ $userCount }}{{ $userLimit ? " / {$userLimit}" : '' }} users
        </a>
    </div>

    {{-- Period filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $p => $label)
            <a href="{{ route('admin.dashboard', ['period' => $p]) }}"
                class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $stats['period'] === $p ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Total Sales</p>
            <p class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">{{ number_format($stats['total_sales'], 2) }} {{ $organization->base_currency }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Paid</p>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['paid'], 2) }} {{ $organization->base_currency }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 sm:p-6 border border-slate-200 shadow-sm">
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Unpaid</p>
            <p class="text-xl sm:text-2xl font-bold text-amber-600 mt-1">{{ number_format($stats['unpaid'], 2) }} {{ $organization->base_currency }}</p>
        </div>
    </div>
</div>
@endsection
