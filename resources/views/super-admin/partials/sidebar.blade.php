<aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 bg-slate-900 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out shrink-0">
    <div class="p-6 border-b border-slate-700">
        <a href="{{ route('super-admin.dashboard') }}" class="text-xl font-bold text-white">Super Admin</a>
    </div>
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <a href="{{ route('super-admin.dashboard') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('super-admin.dashboard') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Dashboard
        </a>
        <a href="{{ route('super-admin.roles.index') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('super-admin.roles.*') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Roles
        </a>
        <a href="{{ route('super-admin.permissions.index') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('super-admin.permissions.*') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Permissions
        </a>
        <a href="{{ route('super-admin.organizations.index') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('super-admin.organizations.*') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Organizations
        </a>
    </nav>
    <div class="p-4 border-t border-slate-700">
        <p class="px-4 py-2 text-sm text-slate-400 truncate" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</p>
        <form method="POST" action="{{ route('super-admin.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-red-400 transition text-left">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>

{{-- Mobile overlay --}}
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden" aria-hidden="true"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (toggle && sidebar && overlay) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden', !sidebar.classList.contains('-translate-x-full'));
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
</script>
