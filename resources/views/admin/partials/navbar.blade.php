<header class="sticky top-0 z-20 h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
    <div class="flex items-center gap-3">
        <button type="button" id="admin-sidebar-toggle" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100" aria-label="Toggle menu">
            <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <a href="{{ route('admin.dashboard') }}" class="font-semibold text-slate-800 hover:text-indigo-600 transition">
            {{ auth()->user()->organization?->name ?? config('app.name') }}
        </a>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm text-slate-500 truncate max-w-[180px]" title="{{ auth()->user()->username ?? auth()->user()->email }}">
            {{ auth()->user()->username ?? auth()->user()->email }}
        </span>
        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-600 hover:text-red-600 transition">Logout</button>
        </form>
    </div>
</header>
