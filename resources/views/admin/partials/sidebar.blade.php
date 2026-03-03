<aside id="admin-sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 bg-slate-900 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out shrink-0">
    <div class="p-6 border-b border-slate-700">
        <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-white">{{ auth()->user()->organization?->name ?? config('app.name') }}</a>
    </div>
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Dashboard
        </a>
        <div class="pt-2">
            <p class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">User Management</p>
            <a href="{{ route('admin.users.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Users List
            </a>
        </div>
    </nav>
    <div class="p-4 border-t border-slate-700">
        <p class="px-4 py-2 text-sm text-slate-400 truncate" title="{{ auth()->user()->username ?? auth()->user()->email }}">{{ auth()->user()->username ?? auth()->user()->email }}</p>
        <form method="POST" action="{{ route('admin.logout') }}">
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
<div id="admin-sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden" aria-hidden="true"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('admin-sidebar-toggle');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('admin-sidebar-overlay');
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
