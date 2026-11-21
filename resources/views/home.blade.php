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
@endphp

<section class="p-5 mb-5 bg-dark text-white rounded-3">
    <h1 class="display-6 fw-bold">Rent a garage for your bicycle, motorcycle, or car</h1>
    <p class="lead mb-4">
        Choose your vehicle and dates, see the full cost before you pay, and keep a garage reserved for the whole period.
    </p>
    <div class="d-flex flex-wrap gap-2">
        @guest
            <a href="{{ route('register') }}" class="btn btn-light btn-lg">Register</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
        @endguest
        @auth
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('renter.dashboard') }}"
               class="btn btn-light btn-lg">Go to dashboard</a>
        @endauth
        <a href="{{ route('features') }}" class="btn btn-outline-light btn-lg">See features</a>
    </div>
</section>

<section class="mb-5">
    <h2 class="h4 mb-3">About the service</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h3 class="h6">Three garage types</h3>
                <p class="text-muted small mb-0">Bicycle, car, and electric car garages, each with its own price.</p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h3 class="h6">Daily or monthly</h3>
                <p class="text-muted small mb-0">A month costs 25 times the daily price. Long rentals are split automatically.</p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h3 class="h6">Clear total cost</h3>
                <p class="text-muted small mb-0">The system calculates the total, including charging, before you save.</p>
            </div></div>
        </div>
    </div>
</section>

<section class="mb-5">
    <h2 class="h4 mb-3">Garage types and prices</h2>
    <div class="row g-3">
        @foreach ($garageTypes as $type)
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h6 mb-1">{{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})</h3>
                        <p class="text-muted small">{{ $accepts[$type->name] ?? '' }}</p>
                        <dl class="row small mb-0">
                            <dt class="col-6">Daily</dt>
                            <dd class="col-6 text-end">{{ $rupiah($type->daily_price) }}</dd>
                            <dt class="col-6">Monthly</dt>
                            <dd class="col-6 text-end">{{ $rupiah($type->monthly_price) }}</dd>
                            @if ($type->name === \App\Models\GarageType::ELECTRIC_CAR)
                                <dt class="col-6">Charging per kWh</dt>
                                <dd class="col-6 text-end">{{ $rupiah($type->electricity_price_per_kwh) }}</dd>
                            @endif
                            <dt class="col-6">Garages</dt>
                            <dd class="col-6 text-end">{{ $type->garages_count }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="mb-4">
    <h2 class="h4 mb-3">How it works</h2>
    <div class="row g-3">
        @foreach ([
            ['1. Register', 'Create a renter account with your NIK, phone number, and email.'],
            ['2. Get approved', 'An admin checks your registration. You can log in after approval.'],
            ['3. Choose and check', 'Enter your vehicle and dates, then pick a free garage and see the total cost.'],
            ['4. Pay and save', 'Upload your payment proof. The garage is then marked as used.'],
        ] as [$title, $text])
            <div class="col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <h3 class="h6">{{ $title }}</h3>
                    <p class="text-muted small mb-0">{{ $text }}</p>
                </div></div>
            </div>
        @endforeach
    </div>
</section>
@endsection