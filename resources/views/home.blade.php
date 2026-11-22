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
    $totalGarages = $garageTypes->sum('garages_count');
    $minPrice = $garageTypes->whereNotNull('daily_price')->min('daily_price');
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $memberUrl = $isAdmin ? route('admin.dashboard') : route('renter.rentals.create');
    $memberLabel = $isAdmin ? 'Go to dashboard' : 'Start a rental';
    $stats = array_filter([
        $totalGarages > 0 ? [$totalGarages, 'Garages'] : null,
        [$garageTypes->count(), 'Garage types'],
        $minPrice !== null ? [$rupiah($minPrice), 'Starting price per day'] : null,
    ]);
@endphp

<section class="bg-black text-white">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:py-20">
        <div>
            <h1 class="font-display text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl">Rent a garage for your vehicle</h1>
            <p class="mt-5 max-w-lg text-neutral-300">Choose your vehicle and dates, see the full cost before you pay, and keep a garage reserved for the whole period.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <x-button :href="route('register')" variant="light" size="lg">Register</x-button>
                    <x-button :href="route('login')" variant="outline-light" size="lg">Login</x-button>
                @endguest
                @auth
                    <x-button :href="$memberUrl" variant="light" size="lg">{{ $memberLabel }}</x-button>
                @endauth
                <x-button :href="route('features')" variant="outline-light" size="lg">See features</x-button>
            </div>
            <div class="mt-10 flex flex-wrap gap-x-10 gap-y-4 border-t border-white/15 pt-6">
                @foreach ($stats as [$value, $label])
                    <div>
                        <p class="font-display text-2xl font-bold">{{ $value }}</p>
                        <p class="text-sm text-neutral-400">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <x-media src="images/hero.webp" :priority="true" class="rounded-2xl" />
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
    <div class="flex flex-col gap-6 rounded-3xl bg-black p-8 text-white sm:p-12 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="font-display text-3xl font-bold tracking-tight">Ready to reserve a garage?</h2>
            <p class="mt-2 text-neutral-300">Create an account and start your first rental once an admin approves it.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @guest
                <x-button :href="route('register')" variant="light" size="lg">Register</x-button>
                <x-button :href="route('login')" variant="outline-light" size="lg">Login</x-button>
            @endguest
            @auth
                <x-button :href="$memberUrl" variant="light" size="lg">{{ $memberLabel }}</x-button>
            @endauth
        </div>
    </div>
</section>
@endsection