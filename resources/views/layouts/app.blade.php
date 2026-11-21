@php
    $navLink = 'rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-neutral-100';
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $dashboard = auth()->check() ? ($isAdmin ? route('admin.dashboard') : route('renter.dashboard')) : null;
    $menuItem = 'block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-neutral-100';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Garage Rental') | Garage Rental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-black antialiased">
<header class="sticky top-0 z-40 border-b border-neutral-200 bg-white">
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-y-2 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 font-display text-lg font-extrabold tracking-tight">
            <span class="grid size-9 place-items-center rounded-lg bg-black">
                <svg class="size-5 stroke-white" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 4l9 6.5V20H3z"/><path d="M7 20v-6h10v6M7 17h10"/></svg>
            </span>
            Garage Rental
        </a>
        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="nav-menu" aria-label="Toggle navigation" class="rounded-lg p-2 hover:bg-neutral-100 md:hidden">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div id="nav-menu" data-nav-menu class="hidden w-full flex-col gap-1 pb-2 md:flex md:w-auto md:flex-1 md:flex-row md:items-center md:pb-0">
            <div class="flex flex-col md:ml-6 md:flex-row md:gap-1">
                <a href="{{ route('home') }}" class="{{ $navLink }} {{ request()->routeIs('home') ? 'bg-neutral-100' : '' }}">Home</a>
                <a href="{{ route('features') }}" class="{{ $navLink }} {{ request()->routeIs('features') ? 'bg-neutral-100' : '' }}">Features</a>
            </div>
            <div class="mt-2 flex flex-col gap-1 md:ml-auto md:mt-0 md:flex-row md:items-center md:gap-2">
                @guest
                    <a href="{{ route('login') }}" class="{{ $navLink }}">Login</a>
                    <x-button :href="route('register')">Register</x-button>
                @endguest
                @auth
                    <a href="{{ $dashboard }}" class="{{ $navLink }} {{ request()->routeIs('admin.dashboard', 'renter.dashboard') ? 'bg-neutral-100' : '' }}">Dashboard</a>
                    <details data-dropdown class="group relative">
                        <summary class="{{ $navLink }} flex cursor-pointer list-none items-center gap-1 [&::-webkit-details-marker]:hidden">
                            {{ auth()->user()->full_name }}
                            <svg class="size-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="mt-1 rounded-xl border border-neutral-200 bg-white p-2 shadow-lg md:absolute md:right-0 md:w-56">
                            @if ($isAdmin)
                                <a href="{{ route('admin.garages.create') }}" class="{{ $menuItem }}">Create Garages</a>
                                <a href="{{ route('admin.renters.index') }}" class="{{ $menuItem }}">Verify Renters</a>
                                <a href="{{ route('admin.rentals.index') }}" class="{{ $menuItem }}">Extend Rentals</a>
                                <a href="{{ route('admin.reports.index') }}" class="{{ $menuItem }}">Reports</a>
                            @else
                                <a href="{{ route('renter.rentals.create') }}" class="{{ $menuItem }}">New Rental</a>
                                <a href="{{ route('renter.rentals.index') }}" class="{{ $menuItem }}">My Rentals</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-neutral-200 pt-1">
                                @csrf
                                <button type="submit" class="{{ $menuItem }}">Logout</button>
                            </form>
                        </div>
                    </details>
                @endauth
            </div>
        </div>
    </nav>
</header>

<main class="flex-1">
    @if (session('success'))
        <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('success') }}</div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-black py-14 text-sm text-neutral-400">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <p class="mb-2 font-display font-bold text-white">Garage Rental</p>
                <p class="max-w-sm">Garages for bicycles, motorcycles, petrol cars, and electric cars, with the full cost shown before you pay.</p>
            </div>
            <div>
                <p class="mb-3 font-semibold text-white">Explore</p>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="hover:text-white hover:underline">Home</a></li>
                    <li><a href="{{ route('features') }}" class="hover:text-white hover:underline">Features</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-3 font-semibold text-white">Account</p>
                <ul class="space-y-2">
                    @guest
                        <li><a href="{{ route('login') }}" class="hover:text-white hover:underline">Login</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white hover:underline">Register</a></li>
                    @endguest
                    @auth
                        <li><a href="{{ $dashboard }}" class="hover:text-white hover:underline">Dashboard</a></li>
                    @endauth
                </ul>
            </div>
        </div>
        <p class="mt-10 border-t border-white/15 pt-6">&copy; {{ now()->year }} Garage Rental</p>
    </div>
</footer>
</body>
</html>