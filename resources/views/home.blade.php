@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    $accepts = [
        'bicycle' => 'Bicycle and motorcycle',
        'car' => 'Petrol car and electric car',
        'electric_car' => 'Electric car, charged to full',
    ];
    $rupiah = fn (?int $amount): string => $amount === null ? 'Not set yet' : 'Rp ' . number_format($amount, 0, ',', '.');
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $memberUrl = $isAdmin ? route('admin.dashboard') : route('renter.rentals.create');
    $memberLabel = $isAdmin ? 'Go to dashboard' : 'Start a rental';
@endphp

<section class="bg-white">
    <div class="mx-auto max-w-6xl px-4 pb-12 pt-12 sm:px-6 lg:pt-20">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h1 class="font-display text-5xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">Rent a garage<br>for your vehicle</h1>
                <p class="mt-5 max-w-md text-neutral-600">Choose your vehicle and dates, see the full cost before you pay, and keep a garage reserved for the whole period.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @guest
                        <x-button :href="route('register')" size="lg">Register</x-button>
                        <x-button :href="route('login')" variant="outline" size="lg">Login</x-button>
                    @endguest
                    @auth
                        <x-button :href="$memberUrl" size="lg">{{ $memberLabel }}</x-button>
                    @endauth
                    @unless ($isAdmin)
                        <x-button :href="route('features')" variant="outline" size="lg">See features</x-button>
                    @endunless
                </div>
            </div>

            <div class="relative mx-auto aspect-4/3 w-full max-w-xl">
                {{-- Fallback illustration, removed as soon as public/images/hero.webp loads. --}}
                <svg data-hero-fallback viewBox="0 0 480 360" fill="none" class="absolute inset-0 size-full" aria-hidden="true">
                    <circle cx="250" cy="180" r="150" fill="#f5f5f5"/>
                    <path d="M70 150 250 62l180 88v138H70z" fill="#e5e5e5"/>
                    <rect x="120" y="176" width="260" height="112" rx="6" fill="#fafafa" stroke="#d4d4d4" stroke-width="3"/>
                    <path d="M120 204h260M120 232h260M120 260h260" stroke="#e5e5e5" stroke-width="3"/>
                    <path d="M110 300c0-16 9-27 25-31l36-10 30-31h96l40 32 36 8c12 3 18 11 18 23v9z" fill="#262626"/>
                    <path d="M196 262l20-22h72l28 24z" fill="#e5e5e5"/>
                    <circle cx="175" cy="302" r="21" fill="#fff" stroke="#262626" stroke-width="9"/>
                    <circle cx="345" cy="302" r="21" fill="#fff" stroke="#262626" stroke-width="9"/>
                    <path d="M40 316h400" stroke="#e5e5e5" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <img src="{{ asset('images/hero.webp') }}" alt="Garage for your vehicle" width="1200" height="900" fetchpriority="high"
                     class="absolute inset-0 size-full object-contain"
                     onload="this.parentElement.querySelector('[data-hero-fallback]')?.remove()"
                     onerror="this.remove()">
            </div>
        </div>
    </div>
</section>

<section class="bg-neutral-100 py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mb-10 max-w-xl">
            <h2 class="font-display text-3xl font-bold tracking-tight">Garage types and prices</h2>
            <p class="mt-2 text-neutral-600">Each type has its own daily price. A month costs 25 times the daily price, and long rentals are split automatically.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($garageTypes as $type)
                @php $typeName = ucwords(str_replace('_', ' ', $type->name)); @endphp
                <article class="flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                    <x-media :src="'images/' . str_replace('_', '-', $type->name) . '.webp'" :alt="$typeName . ' garage'" :code="$type->code" />
                    <div class="flex flex-1 flex-col p-6">
                        <div class="mb-1 flex items-start justify-between gap-2">
                            <h3 class="font-display text-lg font-bold">{{ $typeName }}</h3>
                            <span class="whitespace-nowrap rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold">Type {{ $type->code }}</span>
                        </div>
                        <p class="mb-4 text-sm text-neutral-600">{{ $accepts[$type->name] ?? '' }}</p>
                        <p class="mb-4 font-display text-3xl font-bold tracking-tight">{{ $rupiah($type->daily_price) }} <span class="font-sans text-sm font-medium tracking-normal text-neutral-500">/ day</span></p>
                        <div class="flex justify-between gap-4 border-t border-neutral-200 py-2.5 text-sm"><span class="text-neutral-600">Monthly</span><span>{{ $rupiah($type->monthly_price) }}</span></div>
                        @if ($type->name === \App\Models\GarageType::ELECTRIC_CAR)
                            <div class="flex justify-between gap-4 border-t border-neutral-200 py-2.5 text-sm"><span class="text-neutral-600">Charging per kWh</span><span>{{ $rupiah($type->electricity_price_per_kwh) }}</span></div>
                        @endif
                        <div class="mb-5 flex justify-between gap-4 border-t border-neutral-200 py-2.5 text-sm"><span class="text-neutral-600">Garages</span><span>{{ $type->garages_count }}</span></div>
                        <div class="mt-auto">
                            @guest
                                <x-button :href="route('register')" class="w-full">Register to rent</x-button>
                            @else
                                @if (auth()->user()->isRenter())
                                    <x-button :href="route('renter.rentals.create')" class="w-full">Start a rental</x-button>
                                @endif
                            @endguest
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <h2 class="mb-10 font-display text-3xl font-bold tracking-tight">How it works</h2>
    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['01', 'Register', 'Create a renter account with your NIK, phone number, and email.'],
            ['02', 'Get approved', 'An admin checks your registration. You can log in after approval.'],
            ['03', 'Choose and check', 'Enter your vehicle and dates, then pick a free garage and see the total cost.'],
            ['04', 'Pay and save', 'Upload your payment proof. The garage is then marked as used.'],
        ] as [$no, $title, $text])
            <div class="border-t-2 border-black pt-4">
                <p class="font-display text-sm font-bold text-neutral-500">{{ $no }}</p>
                <h3 class="mb-2 mt-2 font-display font-bold">{{ $title }}</h3>
                <p class="text-sm text-neutral-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
    <div class="grid gap-12 lg:grid-cols-3">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Built around a clear total</h2>
            <p class="mt-2 text-neutral-600">No surprises at payment. Everything is calculated before you save.</p>
        </div>
        <div class="lg:col-span-2">
            @foreach ([
                ['Three garage types', 'Bicycle, car, and electric car garages, each with its own price.'],
                ['Daily or monthly', 'A month costs 25 times the daily price. Long rentals are split automatically.'],
                ['Clear total cost', 'The system calculates the total, including charging, before you save.'],
                ['Verified renters', 'An admin checks every registration before an account can log in.'],
            ] as $i => [$title, $text])
                <div class="grid grid-cols-[3rem_1fr] gap-4 border-t border-neutral-200 py-6 last:border-b">
                    <p class="font-display font-bold text-neutral-500">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</p>
                    <div>
                        <h3 class="font-display font-bold">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-neutral-600">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
    <div class="flex flex-col gap-6 rounded-3xl bg-neutral-100 p-8 sm:p-12 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Ready to reserve a garage?</h2>
            <p class="mt-2 text-neutral-600">Create an account and start your first rental once an admin approves it.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @guest
                <x-button :href="route('register')" size="lg">Register</x-button>
                <x-button :href="route('login')" variant="outline" size="lg">Login</x-button>
            @endguest
            @auth
                <x-button :href="$memberUrl" size="lg">{{ $memberLabel }}</x-button>
            @endauth
        </div>
    </div>
</section>
@endsection