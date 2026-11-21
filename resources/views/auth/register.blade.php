@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="grid min-h-[680px] lg:grid-cols-2">
    <div class="relative hidden items-end overflow-hidden bg-black p-12 text-white lg:flex">
        <img src="{{ asset('images/hero.webp') }}" alt="" class="absolute inset-0 size-full object-cover opacity-50" onerror="this.remove()">
        <div class="relative max-w-sm">
            <h2 class="font-display text-2xl font-bold">Rent with a clear total</h2>
            <p class="mt-2 text-neutral-300">Register once. An admin approves your account, then you can reserve a garage.</p>
        </div>
    </div>
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <h1 class="font-display text-3xl font-bold tracking-tight">Register as Renter</h1>
            <p class="mb-8 mt-1 text-neutral-600">It takes a minute. Approval is done by an admin.</p>
            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                @csrf
                <x-input name="full_name" label="Full name" />
                <x-input name="nik" label="NIK (16 digits)" maxlength="16" inputmode="numeric" />
                <x-input name="phone_number" label="Phone number" type="tel" />
                <x-input name="email" label="Email (used as username)" type="email" />
                <x-input name="password" label="Password (min. 8 characters)" type="password" />
                <x-input name="password_confirmation" label="Confirm password" type="password" />
                <x-button type="submit" class="w-full">Register</x-button>
            </form>
            <p class="mt-6 text-center text-sm">Already registered? <a href="{{ route('login') }}" class="font-semibold underline">Login</a></p>
        </div>
    </div>
</div>
@endsection