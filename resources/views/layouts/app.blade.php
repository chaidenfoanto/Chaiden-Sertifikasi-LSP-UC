<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Garage Rental') | Garage Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        @stack('styles')
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">Garage Rental</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link @if (request()->routeIs('home')) active @endif" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link @if (request()->routeIs('features')) active @endif" href="{{ route('features') }}">Features</a></li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('renter.dashboard') }}">Dashboard</a>
                    </li>
                    @if (auth()->user()->isAdmin())
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.garages.create') }}">Create Garages</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.renters.index') }}">Verify Renters</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.rentals.index') }}">Extend Rentals</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.index') }}">Reports</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('renter.rentals.create') }}">New Rental</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('renter.rentals.index') }}">My Rentals</a></li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="{{ route('register') }}">Register</a></li>
                @endguest
                @auth
                    <li class="nav-item"><span class="navbar-text me-3">{{ auth()->user()->full_name }}</span></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                        </form>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</main>

<footer class="bg-dark text-white-50 py-3 mt-auto">
    <div class="container small">&copy; {{ now()->year }} Garage Rental</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>