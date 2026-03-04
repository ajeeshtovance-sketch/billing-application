<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- no inline styles; rely on compiled assets -->
    @endif
</head>

<body class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 antialiased">
    <header class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center">
            <div class="text-2xl font-bold mb-2 md:mb-0 flex items-center">
                <a href="{{ url('/') }}" class="flex items-center">
                    <img src="https://tovance.com/wp-content/uploads/2025/12/Frame-1.svg" alt="Logo"
                        class="h-16 md:h-20 inline-block mr-2" />
                    <span>{{ config('app.name', 'BillingApp') }}</span>
                </a>
            </div>
            <nav class="space-x-4 flex flex-wrap justify-center">
                <a href="#features" class="text-white hover:text-gray-200">Features</a>
                <a href="#pricing" class="text-white hover:text-gray-200">Pricing</a>
                <a href="#contact" class="text-white hover:text-gray-200">Contact</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-4 py-2 bg-white text-indigo-600 rounded">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-4 py-2 text-indigo-600 bg-white rounded hover:bg-indigo-50">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-4 py-2 bg-white text-indigo-600 rounded hover:bg-indigo-100">Register</a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <main class="flex-grow">
        <section class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-20">
            <div class="max-w-3xl mx-auto text-center px-6">
                <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Simple, powerful billing for your business</h1>
                <p class="text-lg mb-6">Create invoices, manage customers, and track payments all in one place. Built
                    for SaaS and small businesses.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="px-6 py-3 bg-white text-indigo-600 rounded-lg hover:bg-indigo-100">Get Started</a>
                    @endif
                    <a href="#features"
                        class="px-6 py-3 border border-white text-white rounded-lg hover:bg-white hover:text-indigo-600">Learn
                        More</a>
                </div>
            </div>
        </section>

        <!-- banner: user testimonial / call-to-action -->
        <section class="bg-green-500 text-white py-12">
            <div class="max-w-5xl mx-auto px-6 text-center">
                <h2 class="text-2xl font-bold mb-2">Join 10,000+ happy users</h2>
                <p class="mb-4">Sign up today and streamline your billing process.</p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="inline-block px-5 py-2 bg-white text-green-500 rounded hover:bg-green-100">Start Free
                        Trial</a>
                @endif
            </div>
        </section>

        <section id="features" class="py-16">
            <div class="max-w-5xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="bg-white dark:bg-gray-700 rounded-lg p-6 shadow hover:shadow-lg transition">
                    <h2 class="text-2xl font-semibold mb-2 text-indigo-600">Invoicing</h2>
                    <p>Create and send professional invoices in seconds.</p>
                </div>
                <div class="bg-white dark:bg-gray-700 rounded-lg p-6 shadow hover:shadow-lg transition">
                    <h2 class="text-2xl font-semibold mb-2 text-green-600">Customers</h2>
                    <p>Manage your clients and keep everything organized.</p>
                </div>
                <div class="bg-white dark:bg-gray-700 rounded-lg p-6 shadow hover:shadow-lg transition">
                    <h2 class="text-2xl font-semibold mb-2 text-yellow-500">Reports</h2>
                    <p>Track revenue, outstanding payments, and more.</p>
                </div>
            </div>
        </section>

        <!-- banner: limited-time promo -->
        <section class="bg-yellow-400 text-gray-800 py-12">
            <div class="max-w-5xl mx-auto px-6 text-center">
                <h2 class="text-2xl font-bold mb-2">Limited time offer</h2>
                <p class="mb-4">Get 20% off your first year with code <span
                        class="font-mono bg-white px-2 rounded">SAVE20</span>.</p>
            </div>
        </section>

        <section id="pricing" class="bg-gray-100 dark:bg-gray-900 py-16">
            <div class="max-w-5xl mx-auto px-6 text-center">
                <h2 class="text-3xl font-bold mb-4">Pricing that scales with you</h2>
                <p class="mb-8">Affordable plans for freelancers up to enterprises.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-semibold mb-4">Starter</h3>
                        <p class="text-3xl font-bold mb-4">₹7,000<span class="text-base font-normal">/mo</span></p>
                        <ul class="mb-6 space-y-2 text-left">
                            <li>10 invoices/month</li>
                            <li>Basic support</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-5 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Choose</a>
                    </div>
                    <div
                        class="bg-gradient-to-r from-purple-600 to-indigo-500 text-white rounded-lg p-6 shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 border-2 border-white">
                        <h3 class="text-xl font-semibold mb-4">Pro</h3>
                        <p class="text-3xl font-bold mb-4">₹14,999<span class="text-base font-normal">/mo</span></p>
                        <ul class="mb-6 space-y-2 text-left">
                            <li>Unlimited invoices</li>
                            <li>Priority support</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-5 py-2 bg-yellow-400 text-indigo-800 font-semibold rounded hover:bg-yellow-500">Choose</a>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-semibold mb-4">Enterprise</h3>
                        <p class="text-3xl font-bold mb-4">Contact us</p>
                        <ul class="mb-6 space-y-2 text-left">
                            <li>Custom solutions</li>
                            <li>Dedicated support</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-5 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Contact</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-white dark:bg-gray-800 py-6">
        <div class="max-w-7xl mx-auto px-6 text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'BillingApp') }}. All rights reserved.
        </div>
    </footer>
</body>

</html>
