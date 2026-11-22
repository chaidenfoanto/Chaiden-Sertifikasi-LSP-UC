@php
    $navLink = 'relative self-start px-3 py-2 text-sm transition hover:text-neutral-500';
    $navDot = ' font-bold after:absolute after:bottom-0.5 after:left-1/2 after:h-0.5 after:w-5 after:-translate-x-1/2 after:rounded-full after:bg-black';
    $navClass = fn (bool $active): string => $navLink . ($active ? $navDot : ' font-medium');
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $isRenter = auth()->check() && auth()->user()->isRenter();
    $dashboard = $isAdmin ? route('admin.dashboard') : null;
    $brandUrl = $isAdmin ? $dashboard : route('home');
    $menuItem = 'block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-neutral-100';
    $adminLinks = [
        ['Dashboard', 'admin.dashboard', 'admin.dashboard'],
        ['Garages', 'admin.garages.create', 'admin.garages.*'],
        ['Renters', 'admin.renters.index', 'admin.renters.*'],
        ['Rentals', 'admin.rentals.index', 'admin.rentals.*'],
        ['Reports', 'admin.reports.index', 'admin.reports.*'],
    ];
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
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-y-2 px-4 py-3 sm:px-6 md:grid md:grid-cols-[1fr_auto_1fr]">
        <a href="{{ $brandUrl }}" class="whitespace-nowrap font-display text-lg font-extrabold tracking-tight">Garage Rental</a>
        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="nav-menu" aria-label="Toggle navigation" class="rounded-lg p-2 hover:bg-neutral-100 md:hidden">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div id="nav-menu" data-nav-menu class="hidden w-full pb-2 md:contents">
            <div class="flex flex-col md:flex-row md:justify-center md:gap-2">
                @if ($isAdmin)
                    @foreach ($adminLinks as [$label, $route, $pattern])
                        <a href="{{ route($route) }}" class="{{ $navClass(request()->routeIs($pattern)) }}">{{ $label }}</a>
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="{{ $navClass(request()->routeIs('home')) }}">Home</a>
                    <a href="{{ route('features') }}" class="{{ $navClass(request()->routeIs('features')) }}">Features</a>
                    @if ($isRenter)
                        <a href="{{ route('renter.rentals.create') }}" class="{{ $navClass(request()->routeIs('renter.rentals.create')) }}">New Rental</a>
                        <a href="{{ route('renter.rentals.index') }}" class="{{ $navClass(request()->routeIs('renter.rentals.index')) }}">My Rentals</a>
                    @endif
                @endif
            </div>
            <div class="mt-2 flex flex-col gap-1 md:mt-0 md:flex-row md:items-center md:justify-end md:gap-2">
                @guest
                    <a href="{{ route('login') }}" class="{{ $navClass(request()->routeIs('login')) }}">Login</a>
                    <x-button :href="route('register')">Register</x-button>
                @endguest
                @auth
                    <details data-dropdown class="group relative">
                        <summary class="{{ $navClass(false) }} flex cursor-pointer list-none items-center gap-1.5 [&::-webkit-details-marker]:hidden">
                            <span class="max-w-28 truncate lg:max-w-56">{{ auth()->user()->full_name }}</span>
                            <svg class="size-4 shrink-0 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="mt-1 rounded-xl border border-neutral-200 bg-white p-2 shadow-lg md:absolute md:right-0 md:w-48">
                            <form method="POST" action="{{ route('logout') }}">
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
        <div id="toast" class="pointer-events-none fixed inset-x-0 top-20 z-100 flex justify-center px-4">
            <div role="status" class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm text-black shadow-lg">
                <p class="flex-1">{{ session('success') }}</p>
                <button type="button" aria-label="Close message" onclick="document.getElementById('toast')?.remove()" class="-mr-1 text-lg leading-none text-neutral-500 hover:text-black">&times;</button>
            </div>
        </div>
        <script>
            setTimeout(() => document.getElementById('toast')?.remove(), 6000);
        </script>
    @endif

    @yield('content')
</main>

@hasSection('hide_footer')
@else
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
                    @unless ($isAdmin)
                        <li><a href="{{ route('features') }}" class="hover:text-white hover:underline">Features</a></li>
                    @endunless
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
                        @if ($isAdmin)
                            <li><a href="{{ $dashboard }}" class="hover:text-white hover:underline">Dashboard</a></li>
                        @else
                            <li><a href="{{ route('renter.rentals.create') }}" class="hover:text-white hover:underline">New Rental</a></li>
                            <li><a href="{{ route('renter.rentals.index') }}" class="hover:text-white hover:underline">My Rentals</a></li>
                        @endif
                    @endauth
                </ul>
            </div>
        </div>
        <p class="mt-10 border-t border-white/15 pt-6">&copy; {{ now()->year }} Garage Rental</p>
    </div>
</footer>
@endif
@stack('scripts')
</body>
</html>