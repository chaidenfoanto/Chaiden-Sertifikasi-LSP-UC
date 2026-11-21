<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Garage Rental') | Garage Rental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
<header class="site-nav sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#15181d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 4l9 6.5V20H3z"/><path d="M7 20v-6h10v6M7 17h10"/></svg>
                </span>
                Garage Rental
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto ms-lg-4">
                    <li class="nav-item"><a class="nav-link @if (request()->routeIs('home')) active @endif" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link @if (request()->routeIs('features')) active @endif" href="{{ route('features') }}">Features</a></li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    @guest
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                        <li class="nav-item"><a class="btn btn-accent" href="{{ route('register') }}">Register</a></li>
                    @endguest
                    @auth
                        @php $isAdmin = auth()->user()->isAdmin(); @endphp
                        <li class="nav-item">
                            <a class="nav-link @if (request()->routeIs('admin.dashboard', 'renter.dashboard')) active @endif"
                               href="{{ $isAdmin ? route('admin.dashboard') : route('renter.dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->full_name }}</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if ($isAdmin)
                                    <li><a class="dropdown-item" href="{{ route('admin.garages.create') }}">Create Garages</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.renters.index') }}">Verify Renters</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.rentals.index') }}">Extend Rentals</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.reports.index') }}">Reports</a></li>
                                @else
                                    <li><a class="dropdown-item" href="{{ route('renter.rentals.create') }}">New Rental</a></li>
                                    <li><a class="dropdown-item" href="{{ route('renter.rentals.index') }}">My Rentals</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="flex-grow-1">
    @if (session('success'))
        <div class="container pt-4"><div class="alert alert-success mb-0">{{ session('success') }}</div></div>
    @endif

    @hasSection('full')
        @yield('full')
    @else
        <div class="container py-5">@yield('content')</div>
    @endif
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="text-white fw-bold mb-2">Garage Rental</div>
                <p class="mb-0" style="max-width: 24rem;">Garages for bicycles, motorcycles, petrol cars, and electric cars, with the full cost shown before you pay.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Explore</h6>
                <ul class="list-unstyled mb-0 d-grid gap-2">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('features') }}">Features</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Account</h6>
                <ul class="list-unstyled mb-0 d-grid gap-2">
                    @guest
                        <li><a href="{{ route('login') }}">Login</a></li>
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @endguest
                    @auth
                        <li><a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('renter.dashboard') }}">Dashboard</a></li>
                    @endauth
                </ul>
            </div>
        </div>
        <div class="legal">&copy; {{ now()->year }} Garage Rental</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>