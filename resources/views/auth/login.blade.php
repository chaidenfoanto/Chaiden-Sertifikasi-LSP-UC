@extends('layouts.app')

@section('title', 'Login')

@section('full')
<div class="auth-grid">
    <div class="auth-side d-none d-lg-flex">
        <img src="{{ asset('images/hero.webp') }}" alt="" onerror="this.remove()">
        <div class="auth-side-text">
            <h2 class="h3">Welcome back</h2>
            <p class="mb-0 text-white-50">Log in to rent a garage, review your rentals, and check your payment proofs.</p>
        </div>
    </div>
    <div class="auth-form">
        <div>
            <h1 class="h3 mb-1">Login</h1>
            <p class="text-muted mb-4">Use the email you registered with.</p>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-dark w-100 py-2">Login</button>
            </form>
            <p class="text-center mt-4 mb-0">No account yet? <a href="{{ route('register') }}" class="fw-semibold">Register</a></p>
        </div>
    </div>
</div>
@endsection