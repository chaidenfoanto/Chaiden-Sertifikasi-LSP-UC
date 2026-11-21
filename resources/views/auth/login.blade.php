@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="grid min-h-[680px] lg:grid-cols-2">
    <div class="relative hidden items-end overflow-hidden bg-black p-12 text-white lg:flex">
        <img src="{{ asset('images/hero.webp') }}" alt="" class="absolute inset-0 size-full object-cover opacity-50" onerror="this.remove()">
        <div class="relative max-w-sm">
            <h2 class="font-display text-2xl font-bold">Welcome back</h2>
            <p class="mt-2 text-neutral-300">Log in to rent a garage, review your rentals, and check your payment proofs.</p>
        </div>
    </div>
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <h1 class="font-display text-3xl font-bold tracking-tight">Login</h1>
            <p class="mb-8 mt-1 text-neutral-600">Use the email you registered with.</p>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <x-input name="email" label="Email" type="email" autofocus />
                <x-input name="password" label="Password" type="password" />
                <x-button type="submit" class="w-full">Login</x-button>
            </form>
            <p class="mt-6 text-center text-sm">No account yet? <a href="{{ route('register') }}" class="font-semibold underline">Register</a></p>
        </div>
    </div>
</div>
@endsection