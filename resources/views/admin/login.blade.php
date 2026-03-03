@extends('admin.layout')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-slate-200/50">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-800">Organization Login</h1>
                <p class="text-slate-500 mt-1 text-sm">Billing App - Sub-Admin Portal</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700 mb-1.5">Username or Email</label>
                    <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="demo3 or user@yourorg.com"
                        autocomplete="username">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Sign in
                </button>
            </form>
        </div>
        <p class="text-center text-indigo-200/80 text-sm mt-6">
            For organization admins & users. <a href="{{ route('super-admin.login') }}" class="underline hover:text-white">Super Admin login →</a>
        </p>
    </div>
</div>
@endsection
