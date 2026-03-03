<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased" style="font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;">
    @hasSection('sidebar')
    <div class="min-h-screen w-full">
        @include('admin.partials.sidebar')
        <main class="w-full lg:pl-64 min-h-screen bg-slate-50 flex flex-col">
            @include('admin.partials.navbar')
            <div class="flex-1 w-full max-w-[100vw] overflow-x-hidden">
                @yield('content')
            </div>
        </main>
    </div>
    @else
    @yield('content')
    @endif
</body>
</html>
