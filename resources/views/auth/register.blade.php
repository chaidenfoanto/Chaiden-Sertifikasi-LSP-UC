@extends('layouts.app')

@section('title', 'Register')
@section('hide_footer', '1')

@section('content')
<div class="grid min-h-[calc(100vh-65px)] lg:grid-cols-5">
    <div class="relative hidden items-end overflow-hidden bg-black p-12 text-white lg:col-span-2 lg:flex">
        <img src="{{ asset('images/hero.webp') }}" alt="" class="absolute inset-0 size-full object-contain p-10 opacity-50" onerror="this.remove()">
        <div class="relative max-w-sm">
            <h2 class="font-display text-2xl font-bold">Rent with a clear total</h2>
            <p class="mt-2 text-neutral-300">Register once. An admin approves your account, then you can reserve a garage.</p>
        </div>
    </div>
    <div class="flex items-center justify-center px-6 py-12 lg:col-span-3">
        <div class="w-full max-w-sm">
            <h1 class="font-display text-3xl font-bold tracking-tight">Register as Renter</h1>
            <p class="mb-8 mt-1 text-neutral-600">It takes a minute. Approval is done by an admin.</p>
            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                @csrf
                <x-input name="full_name" label="Full name (used as username)" />
                <x-input name="nik" label="NIK (16 digits)" maxlength="16" inputmode="numeric" />
                <div>
                    <label for="phone_number" class="field-label">Phone number</label>
                    <div class="input-group flex">
                        <span class="flex select-none items-center rounded-l-lg border border-r-0 border-neutral-300 bg-neutral-100 px-3.5 text-sm font-semibold text-neutral-600">+62</span>
                        <input id="phone_number" name="phone_number" type="tel" required
                            value="{{ old('phone_number') }}" placeholder="812-3456-7890"
                            maxlength="20" inputmode="numeric" autocomplete="tel-national"
                            @class(['field min-w-0 flex-1 rounded-l-none', 'border-red-600' => $errors->has('phone_number')])>
                    </div>
                    <p class="mt-1.5 text-xs text-neutral-500">Enter the number after +62, without the leading 0.</p>
                    @error('phone_number')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <x-input name="email" label="Email" type="email" />
                <x-input name="password" label="Password (min. 8 characters)" type="password" />
                <x-input name="password_confirmation" label="Confirm password" type="password" />
                <x-button type="submit" class="w-full">Register</x-button>
            </form>
            <p class="mt-6 text-center text-sm">Already registered? <a href="{{ route('login') }}" class="font-semibold underline">Login</a></p>
        </div>
    </div>
</div>
@endsection