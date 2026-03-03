<header class="sticky top-0 z-20 h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
    <div class="flex items-center gap-3 lg:gap-6">
        <button type="button" id="sidebar-toggle" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100" aria-label="Toggle menu">
            <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <a href="{{ route('super-admin.dashboard') }}" class="font-semibold text-slate-800 hover:text-indigo-600 transition">
            {{ config('app.name') }}
        </a>
        <nav class="hidden sm:flex items-center gap-1">
            <a href="{{ route('super-admin.dashboard') }}"
                class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('super-admin.dashboard') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-100' }}">
                Dashboard
            </a>
            <a href="{{ route('super-admin.roles.index') }}"
                class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('super-admin.roles.*') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-100' }}">
                Roles
            </a>
            <a href="{{ route('super-admin.permissions.index') }}"
                class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('super-admin.permissions.*') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-100' }}">
                Permissions
            </a>
            <a href="{{ route('super-admin.organizations.index') }}"
                class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('super-admin.organizations.*') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-100' }}">
                Organizations
            </a>
        </nav>
    </div>
    <div class="flex items-center gap-4">
        <span class="hidden sm:block text-sm text-slate-500 truncate max-w-[180px]" title="{{ auth()->user()->email }}">
            {{ auth()->user()->email }}
        </span>
        <form method="POST" action="{{ route('super-admin.logout') }}" class="inline">
            @csrf
            <button type="submit" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</header>
