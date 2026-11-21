@extends('layouts.app')

@section('title', 'Home')

@php
    $accepts = [
        'bicycle' => 'Bicycle and motorcycle',
        'car' => 'Petrol car and electric car',
        'electric_car' => 'Electric car, charged to full',
    ];
    $rupiah = fn (?int $amount): string => $amount === null ? 'Not set yet' : 'Rp ' . number_format($amount, 0, ',', '.');
    $totalGarages = $garageTypes->sum('garages_count');
    $minPrice = $garageTypes->whereNotNull('daily_price')->min('daily_price');
    $dashboard = auth()->check()
        ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('renter.dashboard'))
        : null;
@endphp

@section('full')
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1 class="mb-3">Rent a garage for your bicycle, motorcycle, or car</h1>
                <p class="lead mb-4">Choose your vehicle and dates, see the full cost before you pay, and keep a garage reserved for the whole period.</p>
                <div class="d-flex flex-wrap gap-2">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-accent btn-lg">Register</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
                    @endguest
                    @auth
                        <a href="{{ $dashboard }}" class="btn btn-accent btn-lg">Go to dashboard</a>
                    @endauth
                    <a href="{{ route('features') }}" class="btn btn-outline-light btn-lg">See features</a>
                </div>
                <div class="hero-stats">
                    @if ($totalGarages > 0)
                        <div><strong>{{ $totalGarages }}</strong><span>Garages</span></div>
                    @endif
                    <div><strong>{{ $garageTypes->count() }}</strong><span>Garage types</span></div>
                    @if ($minPrice !== null)
                        <div><strong>{{ $rupiah($minPrice) }}</strong><span>Starting price per day</span></div>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="media">
                    <img src="{{ asset('images/hero.webp') }}" alt="" width="1600" height="1200" fetchpriority="high" onerror="this.remove()">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2 class="mb-2">Garage types and prices</h2>
            <p class="text-muted mb-0">Each type has its own daily price. A month costs 25 times the daily price, and long rentals are split automatically.</p>
        </div>
        <div class="row g-4">
            @foreach ($garageTypes as $type)
                <div class="col-md-6 col-lg-4">
                    <article class="type-card">
                        <div class="media">
                            <span class="media-code" aria-hidden="true">{{ $type->code }}</span>
                            <img src="{{ asset('images/' . str_replace('_', '-', $type->name) . '.webp') }}"
                                 alt="{{ ucwords(str_replace('_', ' ', $type->name)) }} garage" width="1200" height="900" loading="lazy" onerror="this.remove()">
                        </div>
                        <div class="body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <h3 class="h5 mb-0">{{ ucwords(str_replace('_', ' ', $type->name)) }}</h3>
                                <span class="type-code">Type {{ $type->code }}</span>
                            </div>
                            <p class="text-muted small mb-3">{{ $accepts[$type->name] ?? '' }}</p>
                            <div class="price mb-3">{{ $rupiah($type->daily_price) }} <small>/ day</small></div>
                            <div class="spec"><span>Monthly</span><span>{{ $rupiah($type->monthly_price) }}</span></div>
                            @if ($type->name === \App\Models\GarageType::ELECTRIC_CAR)
                                <div class="spec"><span>Charging per kWh</span><span>{{ $rupiah($type->electricity_price_per_kwh) }}</span></div>
                            @endif
                            <div class="spec mb-3"><span>Garages</span><span>{{ $type->garages_count }}</span></div>
                            <div class="mt-auto">
                                @guest
                                    <a href="{{ route('register') }}" class="btn btn-dark w-100">Register to rent</a>
                                @else
                                    @if (auth()->user()->isRenter())
                                        <a href="{{ route('renter.rentals.create') }}" class="btn btn-dark w-100">Start a rental</a>
                                    @endif
                                @endguest
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="section-head">
            <h2 class="mb-0">How it works</h2>
        </div>
        <div class="steps">
            @foreach ([
                ['01', 'Register', 'Create a renter account with your NIK, phone number, and email.'],
                ['02', 'Get approved', 'An admin checks your registration. You can log in after approval.'],
                ['03', 'Choose and check', 'Enter your vehicle and dates, then pick a free garage and see the total cost.'],
                ['04', 'Pay and save', 'Upload your payment proof. The garage is then marked as used.'],
            ] as [$no, $title, $text])
                <div class="step">
                    <div class="step-no">{{ $no }}</div>
                    <h3 class="h6 mt-2 mb-2">{{ $title }}</h3>
                    <p class="text-muted small mb-0">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h2 class="mb-2">Built around a clear total</h2>
                <p class="text-muted mb-0">No surprises at payment. Everything is calculated before you save.</p>
            </div>
            <div class="col-lg-8">
                @foreach ([
                    ['Three garage types', 'Bicycle, car, and electric car garages, each with its own price.'],
                    ['Daily or monthly', 'A month costs 25 times the daily price. Long rentals are split automatically.'],
                    ['Clear total cost', 'The system calculates the total, including charging, before you save.'],
                    ['Verified renters', 'An admin checks every registration before an account can log in.'],
                ] as $i => [$title, $text])
                    <div class="why-row">
                        <div class="no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <div>
                            <h3 class="h6 mb-1">{{ $title }}</h3>
                            <p class="text-muted small mb-0">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
            <div>
                <h2 class="mb-2">Ready to reserve a garage?</h2>
                <p class="mb-0">Create an account and start your first rental once an admin approves it.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-accent btn-lg">Register</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
                @endguest
                @auth
                    <a href="{{ $dashboard }}" class="btn btn-accent btn-lg">Go to dashboard</a>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection